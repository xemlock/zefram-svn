<?php

require_once 'Zend/Validate/Abstract.php';

class Zefram_Controller_Form_NoRecord extends Zend_Validate_Abstract
{
    const RECORD_EXISTS = 'recordExists';

    protected $_messageTemplates = array(
        self::RECORD_EXISTS => "Record with this identifier already exists in database",
    );
    
    protected $exists;

    public function __construct($record)
    {
        $this->exists = (bool) $record;
    }

    public function isValid($value)
    {
        if ($this->exists) {
            $this->_error(self::RECORD_EXISTS);
            return false;
        }
        return true;
    }
}

// vim: et sw=4
