<?php

/**
 * File MIME type detection based on File_MimeType validator.
 *
 * It is worth noting that implementation of MIME type detection appears twice
 * in ZF, in Zend_File_Transfer_Adapter_Abstract::_detectMimeType() and
 * in Zend_Validate_File::isValid(). And none of them is accessible directly.
 *
 * @version 2014-03-07
 * @author xemlock
 */
class Zefram_File_MimeType
{
    /**
     * @var Zend_Validate_File_MimeType
     */
    protected $_validator;

    /**
     * @param  array|Traversable $options OPTIONAL
     * @return void
     */
    public function __construct($options = null)
    {
        if (is_array($options) || ($options instanceof Traversable)) {
            foreach ($options as $key => $value) {
                $method = 'set' . $key;
                if (method_exists($this, $method)) {
                    $this->{$method}($value);
                }
            }
        }
    }

    /**
     * @param  string $magicFile
     * @return Zefram_File_MimeType
     */
    public function setMagicFile($magicFile)
    {
        $this->_getValidator()->setMagicFile($magicFile);
        return $this;
    }

    /**
     * @param  bool $flag OPTIONAL
     * @return Zefram_File_MimeType
     */
    public function setTryCommonMagicFilesFlag($flag = true)
    {
        $this->_getValidator()->setTryCommonMagicFilesFlag($flag);
        return $this;
    }

    /**
     * Detect MIME Content-type for a file
     *
     * @param  string $file
     * @return string
     * @throws Exception
     */
    public function detect($file)
    {
        if (!Zend_Loader::isReadable($file)) {
            throw new Exception('File is not readable or does not exist');
        }

        $validator = $this->_getValidator();
        $validator->isValid($file);

        $type = $validator->type;

        // file_info detected MS Office files as this application/vnd.ms-office
        if (empty($type) || $type === 'application/vnd.ms-office') {
            $type = Zefram_File_MimeType_Data::detect($file);
        }

        return $type;
    }

    /**
     * @return Zend_Validate_File_MimeType
     * @internal
     */
    protected function _getValidator()
    {
        if (empty($this->_validator)) {
            $this->_validator = new Zend_Validate_File_MimeType(array());
        }
        return $this->_validator;
    }

    /**
     * Detect MIME Content-type for a file
     *
     * @param  string $file
     * @param  array|Traversable $options
     * @return string
     */
    public static function detectMimeType($file, $options = null)
    {
        $instance = new self($options);
        return $instance->detect($file);
    }
}
