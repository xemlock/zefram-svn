<?php

/**
 * Lower case token filter.
 */
class Zefram_Search_Lucene_Analysis_TokenFilter_LowerCase
    extends Zefram_Search_Lucene_Analysis_TokenFilter
{
    /**
     * @var string
     */
    protected $_encoding;

    /**
     * Set filter encoding.
     *
     * @param  string $encoding
     * @return Zefram_Search_Lucene_Analysis_TokenFilter_LowerCase
     * @throws Zend_Search_Lucene_Exception
     */
    public function setEncoding($encoding)
    {
        $encoding = trim($encoding);
        if ($encoding && !function_exists('mb_strtolower')) {
            // mbstring extension is disabled
            throw new Zend_Search_Lucene_Exception('UTF-8 compatible lower case filter needs mbstring extension to be enabled.');
        }
        $this->_encoding = $encoding;
        return $this;
    }

    /**
     * Normalize Token
     *
     * @param Zend_Search_Lucene_Analysis_Token $srcToken
     * @return Zend_Search_Lucene_Analysis_Token
     */
    public function normalize(Zend_Search_Lucene_Analysis_Token $token)
    {
        $text = $this->_toLowerCase($token->getTermText());
        $token->setTermText($text);
        return $token;
    }

    /**
     * Lowercase string.
     *
     * @param  string $text
     * @return string
     */
    protected function _toLowerCase($text)
    {
        if (empty($this->_encoding)) {
            $text = strtolower($text);
        } else {
            $text = mb_strtolower($text, $this->_encoding);
        }
        return $text;
    }
}
