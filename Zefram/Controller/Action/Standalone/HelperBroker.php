<?php

/**
 * Proxy imitating action controller's helper broker.
 *
 * @version 2013-05-03
 */
class Zefram_Controller_Action_Standalone_HelperBroker
{
    protected $_action;

    public function __construct(Zefram_Controller_Action_Standalone $action)
    {
        $this->_action = $action;
    }

    /**
     * @param string $method
     * @param array $args
     * @throws Zefram_Controller_Action_Exception_BadMethodCall
     */
    public function __call($method, $args)
    {
        $helper = $this->_action->getHelper($method);

        if (!method_exists($helper, 'direct')) {
            throw new Zefram_Controller_Action_Exception_BadMethodCall(
                "Helper '$method' does not support overloading via direct()"
            );
        }

        return call_user_func_array(array($helper, 'direct'), $args);
    }

    /**
     * @param string $helper
     */
    public function __get($helper)
    {
        return $this->_action->getHelper($helper);
    }
}
