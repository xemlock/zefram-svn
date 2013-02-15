<?php

/**
 * Class for encapsulation of a standalone action logic.
 *
 * @version 2012-06-19
 */
abstract class Zefram_Controller_Action_Standalone
{
    protected $_controller;

    protected $_helper;

    protected $_request;

    public $view;

    public function __construct(Zend_Controller_Action $controller) 
    {
        $this->_controller = $controller;
        $this->_request    = $controller->getRequest();
        $this->_helper     = new Zefram_Controller_Action_Standalone_HelperBroker($this);

        $this->view = $controller->view;

        $this->_init();
        $this->init();
    }

    protected function _init()
    {}

    /**
     * @deprecated
     */
    public function init()
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
