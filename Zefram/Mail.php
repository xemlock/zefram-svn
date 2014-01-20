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

    public static function setDefaultCharset($charset)
    {
        self::$_defaultCharset = (string) $charset;
    }

    public static function getDefaultCharset()
    {
        return self::$_defaultCharset;
    }

    public static function clearDefaultCharset()
    {
        self::$_defaultCharset = null;
    }

    public static function setDefaultHeaderEncoding($headerEncoding)
    {
        self::$_defaultHeaderEncoding = (string) $headerEncoding;
    }

    public static function getDefaultHeaderEncoding()
    {
        return self::$_defaultHeaderEncoding;
    }

    public static function clearDefaultHeaderEncoding()
    {
        self::$_defaultHeaderEncoding = null;
    }

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
}
