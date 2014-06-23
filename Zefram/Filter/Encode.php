<?php

/**
 * Encode/decode filter.
 *
 * @version 2014-06-23
 * @author xemlock
 */
class Zefram_Filter_Encode extends Zend_Filter_Encrypt
{
    /**
     * @var Zend_Loader_PluginLoader_Interface
     */
    protected $_pluginLoader;

    /**
     * @param  string|array $options
     */
    public function __construct($options = null)
    {
        if (is_object($options) && method_exists($options, 'toArray')) {
            $options = $options->toArray();
        }

        if (is_string($options)) {
            $this->setAdapter($options);
        } else {
            $this->setOptions((array) $options);
        }

        if (empty($this->_adapter)) {
            throw new Zend_Filter_Exception('Encoding adapter was not initialized');
        }
    }

    /**
     * @param  array $options
     * @return Zefram_Filter_Encode
     */
    public function setOptions(array $options)
    {
        if (isset($options['pluginLoader'])) {
            $this->setPluginLoader($options['pluginLoader']);
            unset($options['pluginLoader']);
        }

        if (isset($options['prefixPaths'])) {
            foreach ($options['prefixPaths'] as $prefix => $path) {
                $this->getPluginLoader()->addPrefixPath($prefix, $path);
            }
            unset($options['prefixPaths']);
        }

        if (isset($options['adapter'])) {
            $adapter = $options['adapter'];
            unset($options['adapter']);

            $this->setAdapter($adapter, $options);
        }

        return $this;
    }

    /**
     * Sets encoding options.
     *
     * @param  string $adapter
     * @param  array $options OPTIONAL
     * @return Zefram_Filter_Encode
     */
    public function setAdapter($adapter, array $options = null)
    {
        $adapterClass = $this->getPluginLoader()->load(ucfirst($adapter));
        $adapterObj = new $adapterClass((array) $options);

        if (!$adapterObj instanceof Zefram_Filter_Encode_Interface) {
            throw new Zend_Filter_Exception(sprintf(
                "Encoding adapter '%s' does not implement Zefram_Filter_Encode_Interface",
                $adapter
            ));
        }

        $this->_adapter = $adapterObj;

        return $this;
    }

    /**
     * @return string
     */
    public function getAdapter()
    {
        return $this->_adapter->toString();
    }

    /**
     * Calls adapter methods
     *
     * @param string $method
     * @param array $args
     */
    public function __call($method, $args)
    {
        $part = substr($method, 0, 3);
        if ((($part != 'get') && ($part != 'set')) || !method_exists($this->_adapter, $method)) {
            throw new Zend_Filter_Exception("Unknown method '{$method}'");
        }

        return call_user_func_array(array($this->_adapter, $method), $args);
    }

    /**
     * @param  mixed $value
     * @return mixed
     */
    public function filter($value)
    {
        return $this->_adapter->encode($value);
    }

    /**
     * @return Zend_Loader_PluginLoader_Interface
     */
    public function getPluginLoader()
    {
        if (null === $this->_pluginLoader) {
            $this->_pluginLoader = new Zend_Loader_PluginLoader(array(
                'Zefram_Filter_Encode_' => 'Zefram/Filter/Encode/',
            ));
        }
        return $this->_pluginLoader;
    }

    /**
     * @param  Zend_Loader_PluginLoader_Interface $loader
     * @return Zefram_Validate
     */
    public function setPluginLoader(Zend_Loader_PluginLoader_Interface $loader)
    {
        $this->_pluginLoader = $loader;
        return $this;
    }
}
