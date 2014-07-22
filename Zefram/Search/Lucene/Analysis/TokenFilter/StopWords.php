<?php

/**
 * Token filter that removes stop words.
 */
class Zefram_Search_Lucene_Analysis_TokenFilter_StopWords extends Zefram_Search_Lucene_Analysis_TokenFilter_LowerCase
{
    /**
     * Constructor.
     *
     * @param  array|string $stopWords
     * @param  string $encoding OPTIONAL
     * @return void
     */
    public function __construct($stopWords, $encoding = null)
    {
        parent::__construct($encoding);

        if (is_string($stopWords)) {
            $this->addFromFile($stopWords);
        } else {
            $this->addFromArray($stopWords);
        }
    }

    /**
     * Normalize Token or remove it (if null is returned)
     *
     * @param Zend_Search_Lucene_Analysis_Token $srcToken
     * @return Zend_Search_Lucene_Analysis_Token|null
     */
    public function normalize(Zend_Search_Lucene_Analysis_Token $srcToken)
    {
        $text = $this->_toLowerCase($srcToken->getTermText());
        if (isset($this->_stopWords[$text])) {
            return null;
        } else {
            $srcToken->setTermText($text);
            return $srcToken;
        }
    }

    /**
     * Adds stop words from array.
     *
     * @param  array $data
     * @return Zefram_Search_Lucene_Analysis_TokenFilter_StopWords
     */
    public function addFromArray(array $data)
    {
        $data = array_map('trim', $data);
        $data = array_map(array($this, '_toLowerCase'), $data);
        $this->_stopWords = array_flip($data);
        return $this;
    }

    /**
     * Adds stop words from file.
     *
     * @param  string $file
     * @return Zefram_Search_Lucene_Analysis_TokenFilter_StopWords
     * @throws Zend_Search_Lucene_Exception
     */
    public function addFromFile($file)
    {
        if (!file_exists($file) || !is_readable($file)) {
            throw new Zend_Search_Lucene_Exception('You have to provide valid file path');        
        }
        $fh = @fopen($file, 'r');
        if (!$fd) {
            throw new Zend_Search_Lucene_Exception('Cannot open file ' . $file);
        }
        while (!feof($fh)) {
            $buffer = trim(fgets($fh));
            if (strlen($buffer) > 0 && $buffer[0] != '#') {
                $this->_stopWords[$this->_toLowerCase($buffer)] = 1;
            }
        }
        fclose($fh);
        return $this;
    }
}
