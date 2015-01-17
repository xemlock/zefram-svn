<?php

/**
 * Filter for removing invalid UTF-8 sequences in input string.
 * Based on: http://stackoverflow.com/questions/8215050/#13695364
 *
 * @version 2014-11-07
 * @author  xemlock
 */
class Zefram_Filter_Utf8 implements Zend_Filter_Interface
{
    /**
     * @var string
     */
    protected $_substChar;

    /**
     * Set substitution character(s).
     *
     * @param  string|null $substChar
     * @return Zefram_Filter_Utf8
     */
    public function setSubstChar($substChar = null)
    {
        if ($substChar !== null) {
            $substChar = (string) $substChar;
        }
        $this->_substChar = $substChar;
        return $this;
    }

    /**
     * Get substitution character(s).
     *
     * @return string|null
     */
    public function getSubstChar()
    {
        return $this->_substChar;
    }

    /**
     * Remove invalid UTF-8 sequence from input string.
     *
     * @param  string
     * @return string
     */
    public function filter($value)
    {
        return self::filterStatic($value, $this->_substChar);
    }

    /**
     * Call this filter in a static way.
     *
     * @param  string $value
     * @param  string $substChar OPTIONAL
     * @return string
     */
    public static function filterStatic($value, $substChar = null)
    {
        $prevSubstChar = mb_substitute_character();
        mb_substitute_character(0xFFFD); // codepoint (U+FFFD)

        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');

        if ($substChar !== null) {
            $value = str_replace("\xEF\xBF\xBD", $substChar, $value);
        }

        mb_substitute_character($prevSubstChar);

        return $value;
    }
}
