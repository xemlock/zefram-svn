<?php

class Zefram_Form_Element_HiddenArray extends Zend_Form_Element_Hidden
{
    /**
     * Default view helper to use
     * @var string
     */
    public $helper = 'formHiddenArray';

    /**
     * This element represents an array
     * @var bool
     */
    protected $_isArray = true;

    /**
     * Element decorators
     * @var array
     */
    protected $_decorators = array('ViewHelper');
}
