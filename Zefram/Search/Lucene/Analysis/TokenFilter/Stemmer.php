<?php

abstract class Zefram_Search_Lucene_Analysis_TokenFilter_Stemmer
    extends Zefram_Search_Lucene_Analysis_TokenFilter
{
    /**
     * @var Zefram_Search_Stemmer_StemmerInterface
     */
    protected $_stemmer;

    public function normalize(Zend_Search_Lucene_Analysis_Token $token)
    {
        $text = $this->_stemmer->stem($token->getTermText());
        $token->setTermText($text);
        return $token;
    }
}
