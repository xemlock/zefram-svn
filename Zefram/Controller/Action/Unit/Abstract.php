<?php

/**
 * Class for encapsulation of a single action's logic.
 */
abstract class Zefram_Controller_Action_Unit_Abstract
{
    protected $_controller;

    public function __construct(Zend_Controller_Action $controller) 
    {
        $this->_controller = $controller;
    }

    public function getController()
    {
        return $this->_controller;
    }

    // does anybody know why getParam is protected???
    public function getParam($name, $default = null)
    {
        $value = $this->_controller->getRequest()->getParam($name, $default);
        if (null === $value || '' === $value) {
            $value = $default;
        }
        return $value;
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
        if (method_exists($this->_controller, $name) && is_callable($callback)) {
            return call_user_func_array($callback, $arguments);
        }
        throw new Zend_Controller_Action_Exception(sprintf('Method "%s" does not exist and was not trapped in __call()', $name), 500);
    }

    public function flashMessage($message)
    {
        $this->_controller->getHelper('flashMessenger')->addMessage($message);
    }

    // does anybody know why _redirect is protected???
    public function redirect($url, array $options = array())
    {
        $this->_controller->getHelper('redirector')->gotoUrl($url, $options);
    }
}
