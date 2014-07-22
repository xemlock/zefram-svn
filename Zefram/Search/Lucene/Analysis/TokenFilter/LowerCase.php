<?php

/**
 * Lower case token filter.
 */
class Zefram_Search_Lucene_Analysis_TokenFilter_LowerCase extends Zend_Search_Lucene_Analysis_TokenFilter
{
    /**
     * @var string|null
     */
    protected $_encoding;

    /**
     * Constructor.
     *
     * @param  string $encoding OPTIONAL
     * @return void
     */
    public function __construct($encoding = null)
    {
        if ($encoding !== null && !function_exists('mb_strtolower')) {
            // mbstring extension is disabled
            throw new Zend_Search_Lucene_Exception('UTF-8 compatible lower case filter needs mbstring extension to be enabled.');
        }
        $this->_encoding = $encoding;
    }

    /**
     * Normalize Token
     *
     * @param Zend_Search_Lucene_Analysis_Token $srcToken
     * @return Zend_Search_Lucene_Analysis_Token
     */
    public function normalize(Zend_Search_Lucene_Analysis_Token $srcToken)
    {
        $srcToken->setTermText($this->_toLowerCase($text));
        return $srcToken;
    }

    /**
     * Lowercase string.
     *
     * @param  string $text
     * @return string
     */
    protected function _toLowerCase($text)
    {
        if ($this->_encoding === null) {
            $text = strtolower($text);
        } else {
            $text = mb_strtolower($text, $this->_encoding);
        }
        return $text;
    }
}
