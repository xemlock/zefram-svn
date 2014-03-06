<?php

/**
 * @author xemlock
 * @version 2013-07-06
 */
class Zefram_File_MimeType_Data
{
    const BMP  = 'image/bmp';
    const GIF  = 'image/gif';
    const JPEG = 'image/jpeg';
    const PNG  = 'image/png';
    const TIFF = 'image/tiff';

    const AVI  = 'video/avi';
    const FLV  = 'video/x-flv';
    const MKV  = 'video/x-matroska';
    const MPEG = 'video/mpeg';
    const MP4  = 'video/mp4';
    const WMV  = 'video/x-ms-wmv';

    const PDF  = 'application/pdf'; /* RFC3778 */
    const DOC  = 'application/msword';
    const XLS  = 'application/vnd.ms-excel';
    const PPT  = 'application/vnd.ms-powerpoint';

    const OLE2 = 'application/x-ole-storage';

    const UNKNOWN = 'application/octet-stream';

    protected static $_magic = array(
        // image {{{
        "\x42\x4D" => array(
            'mimetype'  => self::BMP,
            'extension' => 'bmp',
        ),
        "\x47\x49\x46\x38\x37\x61" => array( // GIF87a
            'mimetype'  => self::GIF,
            'extension' => 'gif',
        ),
        "\x47\x49\x46\x38\x39\x61" => array( // GIF89a
            'mimetype'  => self::GIF,
            'extension' => 'gif',
        ),
        "\xFF\xD8" => array(
            'mimetype'  => array(
                self::JPEG,
                'image/jpg',
                'image/pjpeg', // IE7
            ),
            'extension' => array('jpg', 'jpeg', 'jpe'),
        ),
        "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A" => array(
            'mimetype'  => self::PNG,
            'extension' => 'png',
        ),
        "\x49\x49\x2A\x00" => array( // II little-endian TIFF (Intel)
            'mimetype'  => self::TIFF,
            'extension' => array('tif', 'tiff'),
        ),
        "\x4D\x4D\x00\x2A" => array( // MM big-endian TIFF (Motorola)
            'mimetype'  => self::TIFF,
            'extension' => array('tif', 'tiff'),
        ),
        // }}}
        // video {{{
        "\x52\x49\x46\x46" => array(
            'mimetype'  => array(
                self::AVI,
                'video/vnd.avi',
                'video/msvideo',
                'video/x-msvideo',
            ),
            'extension' => 'avi',
        ),
        "\x46\x4C\x56\x01" => array(
            'mimetype'  => self::FLV,
            'extension' => 'flv',
        ),
        "\x00\x00\x01" => array(
            'mimetype'  => self::MPEG,
            'extension' => array('mpg', 'mpeg', 'mpe'),
        ),
        "\x1A\x45\xDF\xA3\x93\x42\x82\x88\x6D\x61\x74\x72\x6F\x73\x6B\x61\x42\x87\x81\x01\x42\x85\x81\x01\x18\x53\x80\x67" => array( // .E...B..matroskaB...B....S.g
            'mimetype'  => self::MKV,
            'extension' => 'mkv',
        ),
        // ISO Base Media file (MPEG-4) v1
        "\x00\x00\x00\x14\x66\x74\x79\x70\x69\x73\x6F\x6D" => array( // ....ftypisom
            'mimetype'  => self::MP4,
            'extension' => 'mp4',
        ),
        // MPEG-4 video files
        "\x00\x00\x00\x18\x66\x74\x79\x70\x33\x67\x70\x35" => array( // ....ftyp3gp5
            'mimetype'  => self::MP4,
            'extension' => 'mp4',
        ),
        // MPEG-4 video/QuickTime file
        "\x00\x00\x00\x18\x66\x74\x79\x70\x6d\x70\x34\x32" => array( // ....ftypmp42
            'mimetype'  => self::MP4,
            'extension' => 'mp4',
        ),
        "\x30\x26\xB2\x75\x8E\x66\xCF\x11\xA6\xD9\x00\xAA\x00\x62\xCE\x6C" => array(
            'mimetype'  => self::WMV,
            'extension' => 'wmv',
        ),
        // }}}
        // documents {{{
        "\x25\x50\x44\x46\x2D\x31\x2E" => array(
            'mimetype'  => array(
                self::PDF,
                'application/x-pdf',
                'application/acrobat',
                'applications/vnd.pdf',
                'text/pdf',
                'text/x-pdf',
            ),
            'extension' => 'pdf',  
        ),
        "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1\x00" => array(
            // OLE2 Compound Document (MS Office)
            'mimetype'  => self::OLE2,
            'extension' => '',
        ),
        // }}}
    );

    protected static $_ole2Extension = array(
        'doc' => array(
            'magic' => array(
                "\x00Word.Document.",   // Word.Document.8 Word 97-2003 format
                                        // Word.Document.7 Word 95 format
                                        // Word.Document.6 Word 6 format
            ),
            'mimetype' => array(
                self::DOC,
                'application/msword',
                'application/doc',
                'application/vnd.msword',
                'application/vnd.ms-word',
                'application/winword',
                'application/word',
                'application/x-msw6',
                'application/x-msword',
            ),
        ),
        'xls' => array(
            'magic' => array(
                "\x00Excel.Sheet.",     // Excel.Sheet.8 Excel 97-2003 format
                                        // Excel.Sheet.5 Excel 95 format
                "\x00Microsoft Excel\x00",
            ),
            'mimetype' => array(
                self::XLS,
                'application/msexcel',
                'application/x-msexcel',
                'application/x-ms-excel',
                'application/x-excel',
                'application/x-dos_ms_excel',
                'application/xls',
            ),
        ),
        'ppt' => array(
            'magic' => array(
                "\x00P\x00o\x00w\x00e\x00r\x00P\x00o\x00i\x00n\x00t\x00 \x00D\x00o\x00c\x00u\x00m\x00e\x00n\x00t\x00",
            ),
            'mimetype' => array(
                self::PPT,
                'application/mspowerpoint',
                'application/ms-powerpoint',
                'application/mspowerpnt',
                'application/vnd-mspowerpoint',
            ),
        ),
    );

    public static function hexdump($buffer) // {{{
    {
        $hex = '';
        for ($i = 0; $i < strlen($buffer); ++$i) {
            $hex .= sprintf("\\x%02X", ord($buffer{$i}));
        }
        return $hex;
    } // }}}

    protected static function _detect($header)
    {
        foreach (self::$_magic as $magic => $info) {
            if (!strncmp($header, $magic, strlen($magic))) {
                return is_array($info['mimetype']) ? $info['mimetype'][0] : $info['mimetype'];
            }
        }

        return false;
    }

    /**
     * Detects MIME type of file. Return value of this function can
     * safely be used in Content-Type header, as it always return
     * valid MIME type (application/octet-stream for unrecoginzed
     * file formats).
     *
     * @param string $filename
     * @return string
     */
    public static function detect($filename) // {{{
    {
        $mimetype = false;

        if (($fh = @fopen($filename, 'r'))) {
            $header = fread($fh, 128);
            fclose($fh);
            $mimetype = self::_detect($header);

            if (self::OLE2 === $mimetype) {
                foreach (self::$_ole2Extension as $ext => $info) {
                    foreach ((array) $info['magic'] as $magic) {
                        if (self::grep($filename, $magic)) {
                            $mimetype = $info['mimetype'][0];
                            break(2);
                        }
                    }
                }
            }
        }

        return $mimetype ? $mimetype : self::UNKNOWN;
    } // }}}

    /**
     * Returns extension corresponding to given MIME type.
     *
     * @param string $mimetype
     * @return false|string
     */
    public static function extension($mimetype) // {{{
    {
        $mimetype = (string) $mimetype;

        if (self::OLE2 === $mimetype) {
            foreach (self::$_ole2Extension as $ext => $info) {
                if (in_array($mimetype, $info['mimetype'])) {
                    return $ext;
                }
            }
        }

        foreach (self::$_magic as $info) {
            $found = false;
            if (is_array($info['mimetype'])) {
                $found = in_array($mimetype, $info['mimetype']);
            } else {
                $found = $info['mimetype'] === $mimetype;
            }
            if ($found) {
                return is_array($info['extension']) ? $info['extension'][0] : $info['extension'];
            }
        }

        return false;
    } // }}}

    public static function grep($filename, $string)
    {
        $string = (string) $string;
        $len = strlen($string);
        $found = false;

        if (0 === $len) {
            return false;
        }

        $log = false;
        if (($fh = @fopen($filename, 'r'))) {
            // read ~1MB at a time
            $chunksize = floor(1024 * 1024 / $len) * $len;

            if ($log) {
                echo 'Search: ', $string, " (", $len, ")\n";
                echo 'Chunksize: ', $chunksize, "\n";
                echo "Memory used: ", memory_get_usage(), "\n\n";
            }

            $prev = '';
            $offset = 0;

            while ($line = fread($fh, $chunksize)) {
                if ($log) echo "* Read " . strlen($line) . " bytes\n";
                $subject = $prev . $line;
                if ($log) echo "  Memory used: ", memory_get_usage(), "\n";
                if (false !== ($pos = strpos($subject, $string))) {
                    if ($log) echo '  Found at affset: ' . ($offset + $pos);
                    $found = true;
                    break;
                }
                $prev = $line;
                $offset .= strlen($line);
            }
            fclose($fh);
        } else {
            if ($log) echo "Unable do open file: ", $filename, "\n";
        }
        if ($log) echo "\n----------\n";
        return $found;
    }
}

// vim: sw=4
