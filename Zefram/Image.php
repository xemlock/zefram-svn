<?php

/**
 * GD based image manipulation class.
 *
 * @version 2014-12-03
 * @author xemlock
 */
class Zefram_Image
{
    const PNG  = IMAGETYPE_PNG;
    const GIF  = IMAGETYPE_GIF;
    const JPEG = IMAGETYPE_JPEG;
    const WBMP = IMAGETYPE_WBMP;

    const GRAYSCALE_AVG        = 0; // averaging (aka quick and dirty)
    const GRAYSCALE_LUMA       = 1; // correcting for the human eye
    const GRAYSCALE_DESATURATE = 2;

    const INFO_TYPE      = 'type';
    const INFO_MIME      = 'mime';
    const INFO_BITS      = 'bits';
    const INFO_WIDTH     = 'width';
    const INFO_HEIGHT    = 'height';
    const INFO_EXTENSION = 'extension';

    protected $_refcount; // reference counter for image resource (_handle)
    protected $_filename;
    protected $_handle;
    protected $_width;
    protected $_height;
    protected $_type;

    /**
     * @param string|resource|Image $image
     */
    public function __construct($image) // {{{
    {
        if (is_resource($image)) {
            $this->_initFromResource($image);
            return;
        }

        if ($image instanceof Zefram_Image) {
            $this->_initFromResource($image->_handle);
            $this->_type     = $image->_type;
            $this->_filename = $image->_filename;

            // use source image reference counter, as we are attaching
            // _handle to the other one's image resource
            $this->_refcount = &$image->_refcount;
            ++$this->_refcount;

            return;
        }

        $attr = getimagesize($image);

        if (empty($attr)) {
            throw new Zefram_Image_Exception('Unable to open image: ' . $image);
        }

        $width  = $attr[0];
        $height = $attr[1];
        $type   = $attr[2];

        switch ($type) {
            case self::GIF:
                $handle = @imagecreatefromgif($image);
                break;

            case self::JPEG:
                $handle = @imagecreatefromjpeg($image);
                break;

            case self::PNG:
                $handle = @imagecreatefrompng($image);
                break;

            case self::WBMP:
                $handle = @imagecreatefromwbmp($image);
                break;
        }

        if (empty($handle)) {
            throw new Zefram_Image_Exception('Unable to create image from file');
        }

        $this->_handle   = $handle;
        $this->_refcount = 1;
        $this->_filename = realpath($image);
        $this->_width    = $width;
        $this->_height   = $height;
        $this->_type     = $type;
    } // }}}

    public function __destruct() // {{{
    {
        --$this->_refcount;
        if (0 == $this->_refcount) {
            imagedestroy($this->_handle);
        }
    } // }}}

    /**
     * @return resource
     */
    public function getHandle() // {{{
    {
        return $this->_handle;
    } // }}}

    public function save($filename = null, $type = null, $quality = 90) // {{{
    {
        if (null === $filename) {
            $filename = $this->_filename;
        }

        if (empty($filename)) {
            throw new Zefram_Image_Exception('Unable to save image: no file name given');
        }

        if (null === $type) {
            // no type given explicitly, try to deduce the type from filename
            // extension, if that fails use default type
            if (false !== ($p = strrpos($filename, '.'))) {
                $ext = strtolower(substr($filename, $p + 1));
            } else {
                $ext = null;
            }

            switch ($ext) {
                case 'jpg':
                case 'jpe':
                case 'jpeg':
                    $type = self::JPEG;
                    break;

                case 'gif':
                    $type = self::GIF;
                    break;

                case 'png':
                    $type = self::PNG;
                    break;

                default:
                    $type = $this->_type;
                    break;
            }
        }

        switch ($type) {
            case self::GIF:
                return imagegif($this->_handle, $filename);

            case self::JPEG:
                return imagejpeg($this->_handle, $filename, $quality);

            case self::PNG:
                return imagepng($this->_handle, $filename);

            default:
                throw new Zefram_Image_Exception('Unsupported image type: ' . $type);
        }
    } // }}}

    public function grayscale($method = self::GRAYSCALE_LUMA) // {{{
    {
        // create paletted based image
        $handle = imagecreate($this->_width, $this->_height);

        // create 256 color palette
        $palette = array();
        for ($c = 0; $c < 256; ++$c) {
            $palette[$c] = imagecolorallocate($handle, $c, $c, $c);
        }

        for ($y = 0; $y < $this->_height; ++$y) {
            for ($x = 0; $x < $this->_width; ++$x) {
                $rgb = imagecolorat($this->_handle, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                switch ($method) {
                    case self::GRAYSCALE_AVG:
                        $gs = round(($r + $g + $b) / 3);
                        break;

                    case self::GRAYSCALE_LUMA:
                        $gs = round(0.2126 * $r + 0.7152 * $g + 0.0722 * $b);
                        break;

                    case self::GRAYSCALE_DESATURATE:
                        $gs = round((max($r, $g, $b) + min($r, $g, $b)) / 2);
                        break;
                }

                imagesetpixel($handle, $x, $y, $palette[$gs]);
            }
        }

        return new self($handle);
    } // }}}

    public function crop($x, $y, $width, $height) // {{{
    {
        $x = intval($x);
        $y = intval($y);
        $width = intval($width);
        $height = intval($height);

        if ($x < 0 
            || $y < 0 
            || $width <= 0
            || $height <= 0
            || ($this->_width < $x + $width)
            || ($this->_height < $y + $height)) 
        {
            throw new Zefram_Image_Exception('Invalid crop coordinates');
        }

        $handle = $this->_createImage($width, $height);

        // bool imagecopyresampled (
        //      resource $dst_image, resource $src_image,
        //      int $dst_x, int $dst_y, int $src_x, int $src_y,
        //      int $dst_w, int $dst_h, int $src_w, int $src_h
        // )
        imagecopyresampled($handle, $this->_handle,
            0, 0, $x, $y,
            $width, $height, $width, $height);

        return new self($handle);
    } // }}}

    public function resize($width = 0, $height = 0) // {{{
    {
        $width  = max(0, (int) $width);
        $height = max(0, (int) $height);

        if ($width + $height === 0) {
            throw new Zefram_Image_Exception('Invalid resize dimensions');
        }

        $ratio = $this->_width / $this->_height;

        if (0 === $width) {
            $width = $height * $ratio;
        }

        if (0 === $height) {
            $height = $width / $ratio;
        }

        $handle = $this->_createImage($width, $height);

        imagecopyresampled($handle, $this->_handle,
            0, 0, 0, 0,
            $width, $height, $this->_width, $this->_height);

        return new self($handle);
    } // }}}

    /**
     * @param int $width
     * @param int $height
     * @param bool $crop OPTIONAL
     */
    public function scale($width = 0, $height = 0, $crop = false) // {{{
    {
        $width  = max(0, (int) $width);
        $height = max(0, (int) $height);

        if ($width + $height === 0) {
            throw new Zefram_Image_Exception('Invalid scale dimensions');
        }

        $ratio = $this->_width / $this->_height;

        if (0 === $width) {
            $width = $height * $ratio;
        }

        if (0 === $height) {
            $height = $width / $ratio;
        }

        $new_ratio = $width / $height;

        if ($crop) {
            // crop central part of original image having $new_ratio ratio
            if ($new_ratio < $ratio) {
                // Central part has the same height as original image, but its
                // width is scaled proportionally according to the new ratio.
                $src_h = $this->_height;
                $src_w = $new_ratio * $src_h;
            } else {
                // Central part has the same width as original image, but its
                // height is scaled proportionally according to the new ratio.
                $src_w = $this->_width;
                $src_h = $src_w / $new_ratio;
            }

            $src_x = ($this->_width - $src_w) / 2;
            $src_y = ($this->_height - $src_h) / 2;

            $dst_x = 0;
            $dst_y = 0;
            $dst_w = $width;
            $dst_h = $height;

        } else {
            // resize whole original image so that it fits inside new image
            if ($new_ratio < $ratio) {
                $dst_w = $width;
                $dst_h = $dst_w / $ratio;
            } else {
                $dst_h = $height;
                $dst_w = $dst_h * $ratio;
            }

            $dst_x = ($width - $dst_w) / 2;
            $dst_y = ($height - $dst_h) / 2;

            $src_x = 0;
            $src_y = 0;
            $src_w = $this->_width;
            $src_h = $this->_height;
        }

        $handle = $this->_createImage($width, $height);

        imagecopyresampled($handle, $this->_handle,
            $dst_x, $dst_y, $src_x, $src_y,
            $dst_w, $dst_h, $src_w, $src_h);

        return new self($handle);
    } // }}}

    protected function _initFromResource($resource) // {{{
    {
        $this->_width    = imagesx($resource);
        $this->_height   = imagesy($resource);
        $this->_handle   = $resource;
        $this->_type     = null;
        $this->_filename = null;
        $this->_refcount = 1; // set this object as owner of resource
    } // }}}

    public function __get($property) // {{{
    {
        switch ($property) {
            case 'type':
                return $this->_type;

            case 'width':
                return $this->_width;

            case 'height':
                return $this->_height;

            case 'filename':
                return $this->_filename;
        }
    } // }}}

    protected function _createImage($width, $height) // {{{
    {
        $handle = imagecreatetruecolor($width, $height);

        imagealphablending($handle, false);
        imagesavealpha($handle, true);

        $transparent = imagecolorallocatealpha($handle, 255, 255, 255, 127);
        imagecolortransparent($handle, $transparent);

        imagefilledrectangle($handle, 0, 0, $width, $height, $transparent); 

        return $handle;
    } // }}}

    /**
     * @param string $path
     * @param string $property OPTIONAL
     * @return mixed
     * @throws Zefram_Image_Exception
     */
    public static function getInfo($path, $property = null) // {{{
    {
        if (false === ($info = @getimagesize($path))) {
            throw new Zefram_Image_Exception('Unable to get image information');
        }

        if (null !== $property) {
            switch ($property) {
                case self::INFO_WIDTH:
                    return $info[0];

                case self::INFO_HEIGHT:
                    return $info[1];

                case self::INFO_TYPE:
                    return $info[2];

                case self::INFO_EXTENSION:
                    return substr(image_type_to_extension($info[2]), 1);

                default:
                    return isset($info[$property]) ? $info[$property] : null;
            }
        }

        // http://php.net/manual/en/function.getimagesize.php:
        // Index 0 and 1 contains respectively the width and the height
        // Index 2 is one of the IMAGETYPE_XXX constants indicating the type

        $info[self::INFO_TYPE] = $info[2];
        $info[self::INFO_WIDTH] = $info[0];
        $info[self::INFO_HEIGHT] = $info[1];
        $info[self::INFO_EXTENSION] = substr(image_type_to_extension($info[2]), 1);

        return $info;
    } // }}}
}
