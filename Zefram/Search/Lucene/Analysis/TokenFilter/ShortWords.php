<?php

/**
 * Short words token filter.
 */
class Zefram_Search_Lucene_Analysis_TokenFilter_ShortWords extends Zend_Search_Lucene_Analysis_TokenFilter
{
    /**
     * @var string|null
     */
    protected $_encoding;

    /**
     * Minimum allowed term length
     * @var integer
     */
    protected $_length;

    /**
     * Constructor.
     *
     * @param  int $short  minimum allowed length of term which passes this filter
     * @param  string $encoding OPTIONAL
     * @return void
     * @throws Zend_Search_Lucene_Exception
     */
    public function __construct($length = 2, $encoding = null)
    {
        if ($encoding !== null && !function_exists('mb_strlen')) {
            // mbstring extension is disabled
            throw new Zend_Search_Lucene_Exception('UTF-8 compatible short words filter needs mbstring extension to be enabled.');
        }
        $this->_encoding = $encoding;
        $this->_length = (int) $length;
    }

    /**
     * Normalize Token or remove it (if null is returned)
     *
     * @param Zend_Search_Lucene_Analysis_Token $srcToken
     * @return Zend_Search_Lucene_Analysis_Token|null
     */
    public function normalize(Zend_Search_Lucene_Analysis_Token $srcToken)
    {
        if ($this->_encoding === null) {
            $length = strlen($srcToken->getTermText());
        } else {
            $length = mb_strlen($srcToken->getTermText(), $this->_encoding);
        }

        if ($length < $this->length) {
            return null;
        } else {
            return $srcToken;
        }
    }
}
