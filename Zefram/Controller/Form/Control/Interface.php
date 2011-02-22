<?php

interface Zefram_Controller_Form_Control_Interface
{
    public function getForm();
    public function getController();
    public function onSubmit();
    public function buildXmlResponse(&$response);
}
