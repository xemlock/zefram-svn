<?php

class Zefram_Filter_StringTruncate implements Zend_Filter_Interface
{
    /**
     * @var int
     */
    protected $_length = 80;

    /**
     * @var string
     */
    protected $_postfix = '...';

    /**
     * @var bool
     */
    protected $_breakWords = false;

    /**
     * @var string
     */
    protected $_encoding = 'utf-8';

    /**
     * @param  array|Traversable $options OPTIONAL
     */
    public function __construct($options = null)
    {
        if ($options) {
            foreach ($options as $key => $value) {
                $method = 'set' . $key;
                if (method_exists($this, $method)) {
                    $this->$method($value);
                }
            }
        }
    }

    /**
     * @param  int $length
     * @return Zefram_Filter_StringTruncate
     */
    public function setLength($length)
    {
        $this->_length = (int) $length;
        return $this;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->_length;
    }

    /**
     * @param  string $postfix
     * @return Zefram_Filter_StringTruncate
     */
    public function setPostfix($postfix)
    {
        $this->_postfix = (string) $postfix;
        return $this;
    }

    /**
     * @return string
     */
    public function getPostfix($postfix)
    {
        return $this->_postfix;
    }

    /**
     * @param  bool $breakWords
     * @return Zefram_Filter_StringTruncate
     */
    public function setBreakWords($breakWords)
    {
        $this->_breakWords = (bool) $breakWords;
        return $this;
    }

    /**
     * @return bool
     */
    public function getBreakWords()
    {
        return $this->_breakWords;
    }

    /**
     * @param  string $encoding
     * @return Zefram_Filter_StringTruncate
     */
    public function setEncoding($encoding)
    {
        $this->_encoding = (string) $encoding;
        return $this;
    }

    /**
     * @return string
     */
    public function getEncoding()
    {
        return $this->_encoding;
    }

    /**
     * @param  string $value
     * @return string
     */
    public function filter($value)
    {
        return self::truncate(
            $value,
            $this->getLength(),
            $this->getPostfix(),
            $this->getBreakWords(),
            $this->getEncoding()
        );
    }

    /**
     * Truncate string to a given length.
     *
     * @param  string $string
     * @param  int $length
     * @param  string $postfix
     * @param  bool $breakWords
     * @param  string $encoding
     * @return string
     */
    public static function truncate($string, $length = 80, $postfix = '...', $breakWords = false, $encoding = 'utf-8') {
        $length = (int) $length;

        if ($length <= 0) {
            return '';
        }

        // trim input string
        $string = (string) $string;
        $string = preg_replace('/(^\s+)|(\s+$)/u', '', $string);

        if (mb_strlen($string, $encoding) > $length) {
            // make room for postfix
            $length -= min($length, mb_strlen($postfix, $encoding));

            // do not break words, truncate string at last whitespace
            // found between 0 and $length index
            if (!$breakWords) {
                $string = preg_replace('/\s+(\S+)?$/u', '', mb_substr($string, 0, $length + 1, $encoding));
                // One of two scenarios took place:
                // - dangling word preceded by whitespace was truncated, or
                // - string is intact, as it consists of a single word
                // length+1 is to properly handle the case, when the last word
                // ends on the border position. Otherwise the last word would
                // not be included in the result, for example:
                // $string = 'a_bc_';
                // truncated to 4 chars we get:
                // when $length + 1 we get 'a_bc'
                // with $length we get 'a'
            }

            return mb_substr($string, 0, $length, $encoding) . $postfix;
        }

        return $string;
    }
}
