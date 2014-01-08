<?php

/**
 * Enhancement to Zend_Locale with added support for codeset and modifier.
 */
class Zefram_Locale extends Zend_Locale
{
    /**
     * @var string
     */
    protected $_codeset;

    /**
     * @var modifier
     */
    protected $_modifier;

    /**
     * Sets a new locale, see {@see Zend_Locale::setLocale()}.
     *
     * @param  @param string|Zend_Locale $locale OPTIONAL
     * @return void
     */
    public function setLocale($locale = null)
    {
        $codeset = null;
        $modifier = null;

        if ($locale instanceof Zefram_Locale) {
            $codeset = $locale->getCodeset();
        } else {
            // POSIX locale format: [language[_territory][.codeset][@modifier]]
            // http://en.wikipedia.org/wiki/Locale
            @list($locale, $codeset) = explode('.', $locale, 2);
            @list($codeset, $modifier) = explode('@', $codeset, 2);
        }

        parent::setLocale($locale);

        $this->_codeset = $codeset;
        $this->_modifier = $modifier;
    }

    /**
     * @return string
     */
    public function getCodeset()
    {
        return $this->_codeset;
    }

    /** 
     * @return string
     */
    public function getModifier()
    {
        return $this->_modifier;
    }

    /**
     * @return string
     */
    public function toString()
    {
        $locale = parent::toString();

        if ($this->_codeset) {
            $locale .= '.' . $this->_codeset;
        }

        if ($this->_modifier) {
            $locale .= '@' . $this->_modifier;
        }

        return $locale;
    }
}
