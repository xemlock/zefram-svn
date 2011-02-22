<?php

abstract class Zefram_Controller_Form_Control implements Zefram_Controller_Form_Control_Interface
{
    protected $_form;
    protected $_controller;

    public function __construct($controller, $form) 
    {
        $this->_controller = $controller;
        $this->_form = $form;
    }

    public function getForm()
    {
        return $this->_form;
    }

    public function getController()
    {
        return $this->_controller;
    }

    public function buildXmlResponse(&$response)
    {
        // nothing to add to response
    }

    public function run()
    {
        Zefram_Controller_Form::processForm($this);
    }
}
