<?php

class Zefram_Mail extends Zend_Mail
{
    /**
     * Default Mail character set
     * @var string
     * @static
     */
    protected static $_defaultCharset = null;

    /**
     * Default encoding of Mail headers
     * @var string
     * @static
     */
    protected static $_defaultHeaderEncoding = null;

    /**
     * @param  string $charset OPTIONAL
     * @return void
     */
    public function __construct($charset = null)
    {
        if (null === $charset) {
            $charset = self::$_defaultCharset;
        }

        parent::__construct($charset);

        if (null !== self::getDefaultFrom()) {
            $this->setFromToDefaultFrom();
        }

        if (null !== self::$_defaultHeaderEncoding) {
            $this->setHeaderEncoding(self::$_defaultHeaderEncoding);
        }
    }

    /**
     * Attach a file to this message.
     *
     * @param  string $path
     * @param  array $options OPTIONAL
     * @return Zefram_Mime_FileStreamPart
     */
    public function attachFile($path, array $options = array())
    {
        $options = array_merge(array(
            'disposition' => Zend_Mime::DISPOSITION_ATTACHMENT,
            'encoding'    => Zend_Mime::ENCODING_BASE64,
        ), $options);

        $part = new Zefram_Mime_FileStreamPart($path, $options);

        if (empty($part->type)) {
            $part->type = Zefram_File_MimeType_Data::detect($part->getPath());
        }

        $this->addAttachment($part);

        return $part;
    }

    /**
     * @param  string $charset
     * @return void
     */
    public static function setDefaultCharset($charset)
    {
        self::$_defaultCharset = (string) $charset;
    }

    /**
     * @return string
     */
    public static function getDefaultCharset()
    {
        return self::$_defaultCharset;
    }

    /**
     * @return void
     */
    public static function clearDefaultCharset()
    {
        self::$_defaultCharset = null;
    }

    /**
     * @param  string $headerEncoding
     * @return void
     */
    public static function setDefaultHeaderEncoding($headerEncoding)
    {
        self::$_defaultHeaderEncoding = (string) $headerEncoding;
    }

    /**
     * @return string
     */
    public static function getDefaultHeaderEncoding()
    {
        return self::$_defaultHeaderEncoding;
    }

    /** 
     * @return void
     */
    public static function clearDefaultHeaderEncoding()
    {
        self::$_defaultHeaderEncoding = null;
    }
}
