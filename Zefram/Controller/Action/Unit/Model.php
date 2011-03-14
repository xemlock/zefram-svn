<?php

class Zefram_Controller_Action_Unit_Model extends Zefram_Controller_Action_Unit_Form
{
    const CREATE = 'CREATE';
    const UPDATE = 'UPDATE';

    protected $_modelName;
    protected $_driver;
    protected $_mode = self::CREATE;
    protected $_formClass = 'Zefram_Form_Model';

    public function __construct($controller)
    {
        $this->_driver = Zefram_Db_Driver::get($this->_modelName);
        parent::__construct($controller);
    }

    public function initForm()
    {
        $row = null;
        $id = $this->getParam($this->_driver->getIdentifier());
        if ($id !== null) {
            $row = $this->_driver->find($id);
        }
        $form = new $this->_formClass($this->_modelName, $this->_mode, $row);
        return $form;
    }

    public function onSubmit()
    {
        $this->_form->updateRecord();
    }
}

// vim: et sw=4 fdm=marker
