<?php

/**
 * This helper allows controllers to get resources in a more elegant way
 * than directly referencing global registry.
 */
class Zefram_Controller_Action_Helper_Resource
    extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * @var object
     */
    protected $_container;

    /**
     * Retrieve resource container.
     *
     * If no resource container is set bootstrap's resource container is used.
     *
     * @return object
     * @throws Exception
     */
    public function getContainer()
    {
        if (empty($this->_container)) {
            // all bootstrapped plugin resources have 'bootstrap' param
            $bootstrap = $this->getFrontController()->getParam('bootstrap');
            $this->setContainer($bootstrap->getContainer());
        }
        return $this->_container;
    }

    /**
     * Set resource container.
     *
     * @param  object $container
     * @return Maniple_Controller_Action_Helper_Resource
     * @throws Exception
     */
    public function setContainer($container)
    {
        if (!is_object($container)) {
            throw new Exception('Resource container must be an object');
        }
        $this->_container = $container;
        return $this;
    }

    /**
     * Retrieve resource from container.
     *
     * @param  string $resource
     * @return mixed
     */
    public function getResource($resource)
    {
        return $this->getContainer()->{$resource};
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
