<?php

/**
 * Built-in Zend_Search_Lucene analyzers are a joke - four classes with
 * copy-paste implementation?
 */
class Zefram_Search_Lucene_Analysis_Analyzer
    extends Zend_Search_Lucene_Analysis_Analyzer
{
    protected $_pluginLoader;

    protected $_encoding;

    protected $_tokenizeNumbers = true;

    protected $_filters = array();

    public function __construct(array $options = null)
    {
        $this->_filterLoader = new 

        if ($options) {
            $this->setOptions($options);
        }
        $this->_checkEncoding($this->_encoding);
    }

    public function setOptions(array $options)
    {
        
    }

    public function setTokenizeNumbers($flag)
    {
        $this->_tokenizeNumbers = (bool) $flag;
        return $this;
    }

    public function setEncoding($encoding)
    {
        $this->_encoding = $this->_checkEncoding($encoding);
        return true;
    }

    /**
     * @return Zend_Loader_PluginLoader_Interface
     */
    public function getPluginLoader()
    {
        if (null === $this->_pluginLoader) {
            $this->_pluginLoader = new Zend_Loader_PluginLoader(array(
                'Zend_Search_Lucene_Analysis_TokenFilter_' => 'Zend/Search/Lucene/Analysis/TokenFilter/',
                'Zefram_Search_Lucene_Analysis_TokenFilter_' => 'Zefram/Search/Lucene/Analysis/TokenFilter/',
            ));
        }
        return $this->_pluginLoader;
    }

    /**
     * @param  Zend_Loader_PluginLoader_Interface $loader
     */
    public function setPluginLoader(Zend_Loader_PluginLoader_Interface $loader)
    {
        $this->_pluginLoader = $loader;
        return $this;
    }

    public function addFilter($filter, $options = null)
    {
        if (!$filter instanceof Zend_Search_Lucene_Analysis_TokenFilter) {
            $class = $this->getPluginLoader()->load($filter);
            if (!is_array($options)) {
                $options = array($options);
            }
            if (empty($options)) {
                $filter = new $class();
            } else {
                $ref = new ReflectionClass($class);
                if ($ref->hasMethod('__construct')) {
                    reset($options);
                    if (is_int(key($options))) {
                        $filter = $ref->newInstanceArgs($options);
                    } else {
                        $filter = $ref->newInstance($options);
                    }
                } else {
                    $filter = $ref->newInstance();
                }
            }
        }
        $this->_filters[] = $filter;
    }

    protected function _checkEncoding($encoding)
    {
        $encoding = (string) $encoding;

        if (!strcasecmp($encoding, 'utf8') || !strcasecmp($encoding, 'utf-8')) {
            if (@preg_match('/\pL/u', 'a') != 1) {
                // PCRE unicode support is turned off
                throw new Zend_Search_Lucene_Exception('Analyzer needs PCRE unicode support to be enabled.');
            }
            $encoding = 'UTF-8';
        }

        return $encoding;
    }

    public function reset()
    {
        $this->_position     = 0;
        $this->_bytePosition = 0;

        // convert input into UTF-8
        if (strcasecmp($this->_encoding, 'utf8' ) != 0  &&
            strcasecmp($this->_encoding, 'utf-8') != 0 ) {
                $this->_input = iconv($this->_encoding, 'UTF-8', $this->_input);
                $this->_encoding = 'UTF-8';
        }
    }
}
