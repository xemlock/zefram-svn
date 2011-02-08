<?php

require_once 'Zend/Controller/Action.php';

abstract class Zefram_Controller_Base extends Zend_Controller_Action
{
    public function init() 
    {
        parent::init();

        // FIXME to nie jest konieczne. baseUrl mozna wyczytac z _request
        $this->view->baseUrl = Zend_Registry::get('baseUrl');
    }

    protected function _isAjaxRequest()
    {
        return $this->_request->isXmlHttpRequest() || (null !== $this->_request->getParam('ajax'));
    }
}

// vim: et sw=4
