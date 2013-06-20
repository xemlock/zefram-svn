<?php

/**
 * Abstract base class for application services
 *
 * @uses       Zend_Application_Bootstrap_Bootstrapper
 * @category   Zefram
 * @package    Zefram_Application
 * @copyright  Copyright (c) 2013 xemlock
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zefram_Application_Service
{
    /**
     * Bootstrap this service is attached to
     *
     * @var Zend_Application_Bootstrap_BootstrapAbstract
     */
    protected $_bootstrap;

    /**
     * Options to skip when calling setOptions()
     *
     * @var array
     */
    protected $_skipOptions = array(
        'options',
    );

    /**
     * Create an instance with bootstrap and options
     *
     * @param Zend_Application_Bootstrap_BootstrapAbstract $bootstrap
     * @param mixed $options OPTIONAL
     */
    public function __construct($bootstrap, $options = null)
    {
        if (is_object($options) && method_exists($options, 'toArray')) {
            $options = $options->toArray();
        }

        $this->setBootstrap($bootstrap);
        $this->setOptions((array) $options);
    }

    /**
     * Set the bootstrap to which this service is attached
     *
     * @param  Zend_Application_Bootstrap_Bootstrapper $bootstrap
     * @return Zefram_Application_Service
     */
    public function setBootstrap(Zend_Application_Bootstrap_Bootstrapper $bootstrap)
    {
        $this->_bootstrap = $bootstrap;
        return $this;
    }

    /**
     * Retrieve the bootstrap to which this service is attached
     *
     * @return Zend_Application_Bootstrap_Bootstrapper
     */
    public function getBootstrap()
    {
        return $this->_bootstrap;
    }

    /**
     * Set options from array
     *
     * @param  array $options Configuration for resource
     * @return Zefram_Application_Service
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            if (in_array(strtolower($key), $this->_skipOptions)) {
                continue;
            }

            $method = 'set' . $key;
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
     * Retrieve a resource from the bootstrap.
     *
     * @param  string $name
     * @return null|mixed
     */
    public function getBootstrapResource($name)
    {
        return $this->getBootstrap()->getResource($name);
    }
}
