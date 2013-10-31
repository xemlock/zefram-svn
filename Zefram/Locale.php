<?php

/**
 * Locale with encoding (language[_territory][.codeset])
 */
class Zefram_Locale extends Zend_Locale
{
    /**
     * @var string
     */
    protected $_encoding;

    /**
     * @param string|Zend_Locale $locale
     */
    public function setLocale($locale)
    {
        $locale = (string) $locale;

        if (false !== strpos($locale, '.')) {
            list($locale, $encoding) = explode('.', $locale, 2);
            $encoding = strtoupper($encoding);
        } else {
            $encoding = null;
        }

        $this->_encoding = $encoding;

        return parent::setLocale($locale);
    }

    /**
     * @return null|string
     */
    public function getEncoding()
    {
        return $this->_encoding;
    }

    /**
     * @return string
     */
    public function toString()
    {
        $locale = parent::toString();

        if ($this->_encoding) {
            return $locale . '.' . $this->_encoding;
        }

        return $locale;
    }
}
