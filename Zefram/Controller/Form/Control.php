<?php

interface Zefram_Controller_Form_Control
{
    public function getForm();
    public function getController();
    public function onSubmit();
    public function getRedirect();
    public function buildXmlResponse(&$response);
}