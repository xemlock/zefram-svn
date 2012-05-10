<?php

/**
 * Proxy imitating action controller's helper broker.
 *
 * @version 2012-05-10
 */
class Zefram_Controller_Action_Standalone_HelperBroker
{
    protected $_action;

    public function __construct(Zefram_Controller_Action_Standalone_Abstract $action)
    {
        $this->_action = $action;
    }

    public function __call($method, $args)
    {
        $helper = $this->_action->getHelper($method);
        if (!method_exists($helper, 'direct')) {
            require_once 'Zend/Controller/Action/Exception.php';
            throw new Zend_Controller_Action_Exception('Helper "' . $method . '" does not support overloading via direct()');
        }
        return call_user_func_array(array($helper, 'direct'), $args);
    }

    public function __get($helper)
    {
        return $this->_action->getHelper($helper);
    }
}
