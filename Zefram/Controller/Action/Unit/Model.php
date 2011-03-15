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
            $this->_modelName = $options['modelName'];
            unset($options['modelName']);
        }
        if (isset($options['mode'])) {
            $this->_mode = $options['mode'];
            unset($options['mode']);
        }
        parent::__construct($controller, $options);
    }

    public function initForm()
    {
        $id = $this->getParam($this->_idParam);
        $form = new $this->_formClass($this->_modelName, $this->_mode, $id);
        return $form;
    }

    public function onSubmit()
    {
        $this->_form->updateRecord();
    }
}

// vim: et sw=4 fdm=marker
