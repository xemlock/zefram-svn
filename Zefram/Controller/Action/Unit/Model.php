<?php

class Zefram_Controller_Action_Unit_Model extends Zefram_Controller_Action_Unit_Form
{
    const CREATE = 'CREATE';
    const UPDATE = 'UPDATE';

    protected $_modelName;
    protected $_mode = self::CREATE;
    protected $_formClass = 'Zefram_Form_Model';
    protected $_idParam = 'id';

    public function __construct(Zend_Controller_Action $controller, Array $options = array())
    {
        if (isset($options['modelName'])) {
            $this->_modelName = (string) $options['modelName'];
            unset($options['modelName']);
        }
        if (isset($options['mode'])) {
            $this->_mode = (string) $options['mode'];
            unset($options['mode']);
        }
        if (isset($options['formClass'])) {
            $this->_formClass = (string) $options['formClass'];
            unset($options['formClass']);
        }
        parent::__construct($controller, $options);
    }

    public function init()
    {
        parent::init();

        $id = $this->getParam($this->_idParam);
        $this->_form = new $this->_formClass($this->_modelName, $this->_mode, $id);
    }

    public function onSubmit()
    {
        $this->_form->updateRecord();
    }
}

// vim: et sw=4 fdm=marker
