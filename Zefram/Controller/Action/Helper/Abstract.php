<?php

abstract class Zefram_Controller_Action_Helper extends Zend_Controller_Action_Helper_Abstract
{
    protected $_helper;

    public function __construct()
    {
        $this->_helper = new Zefram_Controller_Action_Helper_HelperBroker($this);
    }

    public function getHelper()
    {
        return $this->_helper;
    }
}
