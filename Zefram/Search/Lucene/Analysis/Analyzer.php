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

    protected $_tokenizeNumbers;

    protected $_filters = array();

    public function __construct(array $options = null)
    {
        if ($options) {
            $this->setOptions($options);
        }
    }

    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . $key;
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
        return $this;
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

    /**
     * Add filters to anaylyzer.
     *
     * @param  array $filters
     * @return Zefram_Search_Lucene_Analysis_Analyzer
     */
    public function addFilters(array $filters)
    {
        foreach ($filters as $filter) {
            $this->addFilter($filter);
        }
        return $this;
    }

    public function setFilters(array $filters)
    {
        $this->_filters = array();
        return $this->addFilters($filters);
    }    

    /**
     * Add filter to anaylyzer.
     *
     * @param  Zend_Search_Lucene_Analysis_TokenFilter|array|string $filter
     * @return Zefram_Search_Lucene_Analysis_Analyzer
     */
    public function addFilter($filter)
    {
        if (!$filter instanceof Zend_Search_Lucene_Analysis_TokenFilter) {
            $args = (array) $filter;
            $class = $this->getPluginLoader()->load(array_shift($args));
            if ($args) {
                $ref = new ReflectionClass($class);
                if ($ref->hasMethod('__construct')) {
                    $filter = $ref->newInstanceArgs($args);
                } else {
                    $filter = $ref->newInstance();
                }
            } else {
                $filter = new $class();
            }
        }
        $this->_filters[] = $filter;
        return $this;
    }

    protected function _checkEncoding($encoding)
    {
        $encoding = trim($encoding);

        if (!strcasecmp($encoding, 'utf8') || !strcasecmp($encoding, 'utf-8')) {
            if (@preg_match('/\pL/u', 'a') != 1) {
                // PCRE unicode support is turned off
                throw new Zend_Search_Lucene_Exception('Analyzer needs PCRE unicode support to be enabled.');
            }
            $encoding = 'UTF-8';
        }

        return $encoding;
    }

    /**
     * Reset token stream
     *
     * @return void
     */
    public function reset()
    {
        $this->_position     = 0;
        $this->_bytePosition = 0;

        // convert non-ASCII encoding into UTF-8
        if ($this->_encoding && $this->_encoding !== 'UTF-8') {
            $this->_input = iconv($this->_encoding, 'UTF-8', $this->_input);
            $this->setEncoding('UTF-8');
        }
    }

    protected function _getTokenRegex()
    {
        if ($this->_encoding === 'UTF-8') {
            if ($this->_tokenizeNumbers) {
                $regex = '/[\p{L}]+/u';
            } else {
                $regex = '/[\p{L}\p{N}]+/u';
            }
        } else {
            if ($this->_tokenizeNumbers) {
                $regex = '/[a-zA-Z0-9]+/';
            } else {
                $regex = '/[a-zA-Z]+/';
            }
        }
        return $regex;
    }

    /*
     * Get next token.
     *
     * Returns null at the end of stream
     *
     * @return Zend_Search_Lucene_Analysis_Token|null
     */
    public function nextToken()
    {
        if ($this->_input === null) {
            return null;
        }

        $regex = $this->_getTokenRegex();

        do {
            if (!preg_match($regex, $this->_input, $match, PREG_OFFSET_CAPTURE, $this->_bytePosition)) {
                // It covers both cases a) there are no matches (preg_match(...) === 0)
                // b) error occured (preg_match(...) === FALSE)
                return null;
            }

            // matched string
            $matchedWord = $match[0][0];

            // binary position of the matched word in the input stream
            $binStartPos = $match[0][1];

            // character position of the matched word in the input stream
            $startPos = $this->_position +
                        iconv_strlen(substr($this->_input,
                                            $this->_bytePosition,
                                            $binStartPos - $this->_bytePosition),
                                     'UTF-8');
            // character postion of the end of matched word in the input stream
            $endPos = $startPos + iconv_strlen($matchedWord, 'UTF-8');

            $this->_bytePosition = $binStartPos + strlen($matchedWord);
            $this->_position     = $endPos;

            $token = $this->normalize(new Zend_Search_Lucene_Analysis_Token($matchedWord, $startPos, $endPos));
        } while ($token === null); // try again if token is skipped

        return $token;
    }

    public function normalize(Zend_Search_Lucene_Analysis_Token $token)
    {
        foreach ($this->_filters as $filter) {
            $token = $filter->normalize($token);

            // resulting token can be null if the filter removes it
            if ($token === null) {
                return null;
            }
        }

        return $token;
    }
}
