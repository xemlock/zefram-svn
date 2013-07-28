<?php

/**
 * Request accessible from bootstrap
 *
 *   resources.request.class = "Zend_Controller_Request_Http"
 *   resources.request.params.NAME = VALUE
 *
 * To enable request with the default configuration:
 *
 *   resources.request = 1
 */
class Zefram_Application_Resource_Request extends Zend_Application_Resource_ResourceAbstract
{
    protected $_request;

    public function getRequest()
    {
        if (null === $this->_request) {
            $options = $this->getOptions();

            if (empty($options['class'])) {
                $class = 'Zend_Controller_Request_Http';
            } else {
                $class = $options['class'];
            }

            $this->_request = new $class;

            if (isset($options['params']) && is_array($options['params'])) {
                $this->_request->setParams($options['params']);
            }
        }

        return $this->_request;
    }

    public function init()
    {
        $bootstrap = $this->getBootstrap();
        $bootstrap->bootstrap('FrontController');

        $request = $this->getRequest();

        $frontController = $bootstrap->getResource('FrontController');
        $frontController->setRequest($request);

        return $request;
    }
}
