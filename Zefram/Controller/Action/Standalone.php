<?php

/**
 * Class for encapsulation of a standalone action logic.
 *
 * @version 2013-06-30 / 2013-05-02
 */
abstract class Zefram_Controller_Action_Standalone
{
    protected $_actionControllerClass;

    protected $_actionController;

    protected $_helper;

    protected $_request;

    protected $_response;

    public $view;

    /**
     * @param  Zend_Controller_Action $controller
     * @throws Zefram_Controller_Action_Exception_InvalidArgument
     */
    public function __construct(Zend_Controller_Action $actionController) 
    {
        if (null !== $this->_actionControllerClass && !$actionController instanceof $this->_actionControllerClass) {
            throw new Zefram_Controller_Action_Exception_InvalidArgument(sprintf(
                "The specified controller is of class %s, expecting class to be an instance of %s",
                get_class($actionController),
                $this->_actionControllerClass
            ));
        }

        $this->_actionController = $actionController;

        $this->_request = $actionController->getRequest();
        $this->_response = $actionController->getResponse();

        $this->_helper = new Zefram_Controller_Action_Standalone_HelperBroker($this);
        $this->view = $actionController->view;

        $this->_init();
    }

    protected function _init()
    {}

    public function getActionController()
    {
        return $this->_actionController;
    }

    /**
     * Proxies to {@link getActionController()}.
     * @deprecated
     */
    public function getController()
    {
        return $this->getActionController();
    }

    public function getView()
    {
        return $this->_actionController->initView();
    }

    /**
     * Since this method is used by helper broker, for performance reasons
     * it is declared here and not discovered using __call magic method.
     *
     * @param  $name
     * @return Zend_Controller_Action_Helper_Abstract
     */
    public function getHelper($name)
    {
        return $this->_actionController->getHelper($name);
    }

    abstract public function run();

    /**
     * Call action controller method.
     *
     * @param  string $name
     * @param  array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // is_callable returns true if __call is present.
        $callback = array($this->_actionController, $name);
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

    protected function _flashMessage($message, $namespace = null)
    {
        $this->_helper->flashMessenger->addMessage($message, $namespace);
    }
}
