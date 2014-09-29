<?php

/**
 * This helper allows controllers to access bootstrap resources without
 * directly referencing bootstrap, front controller, or global registry.
 *
 * @version 2014-09-29
 * @author xemlock
 */
class Zefram_Controller_Action_Helper_Resource
    extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * @var Zend_Application_Bootstrap_BootstrapAbstract
     */
    protected $_bootstrap;

    /**
     * Retrieve bootstrap.
     *
     * @return Zend_Application_Bootstrap_BootstrapAbstract
     */
    public function getBootstrap()
    {
        if (empty($this->_bootstrap)) {
            // all bootstrapped plugin resources have 'bootstrap' param
            $bootstrap = $this->getFrontController()->getParam('bootstrap');
            $this->setBootstrap($bootstrap);
        }
        return $this->_bootstrap;
    }

    /**
     * Set bootstrap.
     *
     * @param  Zend_Application_Bootstrap_BootstrapAbstract $bootstrap
     * @return Zefram_Controller_Action_Helper_Resource
     */
    public function setBootstrap(Zend_Application_Bootstrap_BootstrapAbstract $bootstrap)
    {
        $this->_bootstrap = $bootstrap;
        return $this;
    }

    /**
     * Retrieve resource from bootstrap.
     *
     * @param  string $resource
     * @return mixed
     */
    public function getResource($resource)
    {
        return $this->getBootstrap()->getResource($resource);
    }

    /**
     * Proxy to {@link getResource()}.
     *
     * @param  string $resource
     * @return mixed
     */
    public function direct($resource)
    {
        return $this->getResource($resource);
    }
}
