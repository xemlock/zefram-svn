<?php

/**
 * Class for encapsulation of a standalone action logic.
 *
 * @version 2013-05-02
 */
abstract class Zefram_Controller_Action_Standalone
{
    protected $_controllerClass;

    protected $_controller;

    protected $_helper;

    protected $_request;

    public $view;

    public function __construct(Zend_Controller_Action $controller) 
    {
        if (null !== $this->_controllerClass && !$controller instanceof $this->_controllerClass) {
            throw new Zefram_Controller_Action_Standalone_Exception_InvalidArgument(sprintf(
                "The specified controller is of class %s, expecting class to be an instance of %s",
                is_object($controller) ? get_class($controller) : gettype($controller),
                $this->_controllerClass
            ));
        }

        $this->_controller = $controller;
        $this->_request = $controller->getRequest();
        $this->_helper = new Zefram_Controller_Action_Standalone_HelperBroker($this);
        $this->view = $controller->view;

        $this->_init();
    }

    protected function _init()
    {}

    public function getController()
    {
        return $this->_controller;
    }

    public function getView()
    {
        return $this->_controller->initView();
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
        $value = $this->_request->getParam($name, $default);
        if (null === $value || '' === $value) {
            $value = $default;
        }
        return $value;
    }

    protected function _redirect($url, array $options = array())
    {
        $this->_helper->redirector->gotoUrl($url, $options);
    }

    protected function _flashMessage($message)
    {
        $this->_helper->flashMessenger->addMessage($message);
    }
}
