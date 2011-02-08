<?php

require_once 'ZUtils/Controller/Form.php';

class ZUtils_Controller_Model extends ZUtils_Controller_Form
{
    protected $modelName;
    protected $driver;
    protected $formClass = 'ZUtils_Form_Model';

    public function init() 
    {
        parent::init();

        if (null === $this->modelName) {
            $this->modelName = preg_replace('/Controller$/i', '', get_class($this));
        }
        $this->driver = ZUtils_Db_Driver::get($this->modelName);
    }

    /**
     * Method launched before loading data to a row, prior to saving
     * into database. Row may be null when adding new record, not-null
     * upon edition.
     */
    protected function beforeLoad(&$data, $row) {}

    protected function _createForm($mode) 
    {
        $row = null;
        $id = $this->_request->getParam($this->driver->getIdentifier());
        if ($id !== null) {
            $row = $this->driver->find($id);
        }
        $form = new $this->formClass($this->modelName, $mode, $row);
        foreach ($form as $field) {
            if ($field->isRequired()) {
                $label = $field->getDecorator('Label');
                if (!$label) continue;
                $label->setOption('escape', false);
                $label->setRequiredSuffix('<small style="color:red;" title="Required field">*</small>');
            }
        }
        $this->form = $form;
        return array($form, array('row' => $row));
    }

    protected function _processSentData($form, &$context) 
    {
        $form->updateRecord();
    }

    protected function _redirectAfterSave($context) 
    {
        // get processed record PK name and value
        $pk = $this->driver->getIdentifier();
        $id = $this->form->getRecord()->{$pk};

        // go to edit page of inserted/edited row
        $params = $this->_request->getUserParams();
        $redir = array($params['controller'], 'edit');
        unset($params['controller'], $params['action']);
        if ($params['module'] == 'default') unset($params['module']);
        if (isset($params[$pk])) unset($params[$pk]);
        $redir[] = $pk;
        $redir[] = $id;
        foreach ($params as $key => $value) {
            $redir[] = $key;
            $redir[] = $value;
        }
        $redir = '/' . implode('/', $redir);
        return $redir;
    }

    protected function _process($mode) 
    {
        list($form, $context) = $this->_createForm($mode);
        $this->_processForm($form, $context);

    }

    public function addAction()  { $this->_process(ZUtils_Form_Model::CREATE); }
    public function editAction() { $this->_process(ZUtils_Form_Model::UPDATE); }
}

// vim: et sw=4 fdm=marker
