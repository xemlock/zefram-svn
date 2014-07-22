<?php

/**
 * Short words token filter.
 */
class Zefram_Search_Lucene_Analysis_TokenFilter_ShortWords
    extends Zefram_Search_Lucene_Analysis_TokenFilter
{
    /**
     * @var string
     */
    protected $_encoding;

    /**
     * Minimum allowed term length
     * @var integer
     */
    protected $_minLength = 2;

    /**
     * @param  array|int $options
     * @return void
     */
    public function __construct($options = null)
    {
        if (is_int($options)) {
            $options = array('minLength' => $options);
        }
        parent::__construct($options);
    }

    /**
     * Set minimum term length
     *
     * @param  int $minLength
     * @return Zefram_Search_Lucene_Analysis_TokenFilter_ShortWords
     * @throws Zend_Search_Lucene_Exception
     */
    public function setMinLength($minLength)
    {
        $minLength = (int) $minLength;
        if ($minLength < 0) {
            throw new Zend_Search_Lucene_Exception('Minimum length must be greater or equal zero');
        }
        $this->_minLength = $minLength;
        return $this;
    }

    /**
     * Set filter encoding. 
     *
     * @param  string $encoding
     * @return Zefram_Search_Lucene_Analysis_TokenFilter_ShortWords
     * @throws Zend_Search_Lucene_Exception
     */
    public function setEncoding($encoding)
    {
        $encoding = trim($encoding);
        if ($encoding && !function_exists('mb_strlen')) {
            // mbstring extension is disabled
            throw new Zend_Search_Lucene_Exception('UTF-8 compatible short words filter needs mbstring extension to be enabled.');
        }
        $this->_encoding = $encoding;
        return $this;
    }

    /**
     * Normalize Token or remove it (if null is returned)
     *
     * @param Zend_Search_Lucene_Analysis_Token $srcToken
     * @return Zend_Search_Lucene_Analysis_Token|null
     */
    public function normalize(Zend_Search_Lucene_Analysis_Token $srcToken)
    {
        if (empty($this->_encoding)) {
            $length = strlen($srcToken->getTermText());
        } else {
            $length = mb_strlen($srcToken->getTermText(), $this->_encoding);
        }

        if ($length < $this->_minLength) {
            return null;
        } else {
            return $srcToken;
        }
    }
}
