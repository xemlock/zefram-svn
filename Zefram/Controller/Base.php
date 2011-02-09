<?php

require_once 'Zend/Controller/Action.php';

abstract class Zefram_Controller_Base extends Zend_Controller_Action
{
    protected function _isAjaxRequest()
    {
        return $this->_request->isXmlHttpRequest() || (null !== $this->_request->getParam('ajax'));
    }
}

// vim: et sw=4
