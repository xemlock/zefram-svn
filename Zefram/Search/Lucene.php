<?php

/**
 * Provides more encapsulated Lucene implementation.
 */
class Zefram_Search_Lucene extends Zend_Search_Lucene
{
    /**
     * @var Zend_Search_Lucene_Analysis_Analyzer
     */
    protected $_analyzer;

    /**
     * @return Zend_Search_Lucene_Analysis_Analyzer
     */
    public function getAnalyzer()
    {
        if (empty($this->_analyzer)) {
            $this->_analyzer = Zend_Search_Lucene_Analysis_Analyzer::getDefault();
        }
        return $this->_analyzer;
    }

    /**
     * @param  Zend_Search_Lucene_Analysis_Analyzer|null $analyzer
     * @return Zefram_Search_Lucene
     */
    public function setAnalyzer(Zend_Search_Lucene_Analysis_Analyzer $analyzer = null)
    {
        $this->_analyzer = $analyzer;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param  Zend_Search_Lucene_Search_QueryParser|string $query
     * @return Zend_Search_Lucene_Search_QueryHit[]
     * @throws Zend_Search_Lucene_Exception
     */
    public function find($query)
    {
        // calling parent method using call_user_func via 'parent::method'
        // works since PHP 5.1.2
        return $this->_withAnalyzer('parent::find', $query);
    }

    /**
     * {@inheritdoc}
     *
     * @param  Zend_Search_Lucene_Document $document
     */
    public function addDocument(Zend_Search_Lucene_Document $document)
    {
        return $this->_withAnalyzer('parent::addDocument', $document);
    }

    /**
     * @internal
     */
    protected function _withAnalyzer($method)
    {
        $analyzer = $this->_analyzer;

        if ($analyzer) {
            $prevAnalyzer = Zend_Search_Lucene_Analysis_Analyzer::getDefault();
            Zend_Search_Lucene_Analysis_Analyzer::setDefault($analyzer);
        }

        $args = func_get_args();
        array_shift($args);

        $result = call_user_func_array(array($this, $method), $args);

        if ($analyzer) {
            Zend_Search_Lucene_Analysis_Analyzer::setDefault($prevAnalyzer);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param  mixed $directory
     * @return Zend_Search_Lucene_Interface
     */
    public static function open($directory)
    {
        return new Zefram_Search_Lucene_Proxy(new Zefram_Search_Lucene($directory, false));
    }

    /**
     * {@inheritdoc}
     *
     * @param  mixed $directory
     * @return Zend_Search_Lucene_Interface
     */
    public static function create($directory)
    {
        return new Zefram_Search_Lucene_Proxy(new Zefram_Search_Lucene($directory, true));
    }
}
