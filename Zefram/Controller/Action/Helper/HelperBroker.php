<?php

class Zefram_Controller_Action_Helper_HelperBroker
{
    protected $_helper;

    public function __construct(Zefram_Controller_Action_Helper_Abstract $helper)
    {
        $this->_helper = $helper;
    }

    public function __call($method, $args)
    {
        $helper = $this->_helper->getHelper($method);
        if (!method_exists($helper, 'direct')) {
            throw new Zefram_Controller_Action_Standalone_Exception('Helper "' . $method . '" does not support overloading via direct()');
        }
        return call_user_func_array(array($helper, 'direct'), $args);
    }

    public function __get($helper)
    {
        return $this->_helper->getHelper($helper);
    }
}
