<?php

class Zefram_Search_Lucene_Analysis_TokenFilter_Stemmer_PorterStemmer
    extends Zefram_Search_Lucene_Analysis_TokenFilter_Stemmer
{
    public function __construct()
    {
        $this->_stemmer = new Zefram_Search_Stemmer_PorterStemmer();
    }
}
