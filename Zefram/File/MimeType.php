<?php

abstract class Zefram_File_MimeType
{
    /**
     * Detect MIME Content-type for a file
     *
     * Implementation is directly taken from ZF. To be more precise, it appears
     * twice, in Zend_File_Transfer_Adapter_Abstract::_detectMimeType() and
     * in Zend_Validate_File::isValid(). Why it is not available to be called
     * directly? Nevermind. It now is.
     *
     * @param  string $file
     * @param  string $magic
     * @return string
     */
    public static function detectMimeType($file, $magic = null)
    {
        if (class_exists('finfo', false)) {
            $const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
            if (!empty($magic)) {
                $mime = @finfo_open($const, $magic);
            }

            if (empty($mime)) {
                $mime = @finfo_open($const);
            }

            if (!empty($mime)) {
                $result = finfo_file($mime, $file);
            }

            unset($mime);
        }

        if (empty($result) && (function_exists('mime_content_type')
            && ini_get('mime_magic.magicfile'))) {
            $result = mime_content_type($file);
        }

        if (empty($result)) {
            $result = 'application/octet-stream';
        }

        return $result;
    }
}
