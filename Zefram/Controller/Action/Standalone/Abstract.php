<?php

/**
 * Class for encapsulation of a standalone action logic.
 *
 * @version 2012-05-10
 */
abstract class Zefram_Controller_Action_Standalone_Abstract
{
    protected $_controller;
    protected $_helper;


    public function __construct(Zend_Controller_Action $controller) 
    {
        $this->_controller = $controller;
        $this->_helper = new Zefram_Controller_Action_Standalone_HelperBroker($this);
        $this->init();
    }

    public function init()
    {}

    public function getController()
    {
        return $this->_controller;
    }

    public function getView()
    {
        return $this->_controller->view;
    }

    abstract public function run();

    // call controller methods
    public function __call($name, $arguments)
    {
        // is_callable returns true if __call is present.
        $callback = array($this->_controller, $name);
        return call_user_func_array($callback, $arguments);
    }

    protected function _getParam($name, $default = null)
    {
        $value = $this->_controller->getRequest()->getParam($name, $default);
        if (null === $value || '' === $value) {
            $value = $default;
        }
        return $value;
    }

    protected function _redirect($url, array $options = array())
    {
        $this->_controller->getHelper('redirector')->gotoUrl($url, $options);
    }

    protected function _json($data)
    {
        $this->_controller->getHelper('json')->sendJson($data);
    }

    protected function _flashMessage($message)
    {
        $this->_controller->getHelper('flashMessenger')->addMessage($message);
    }
}
