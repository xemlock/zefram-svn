<?php

class Zefram_Controller_Model extends Zefram_Controller_Action
{
    protected $_modelName;

    public function addAction()
    {
        $action = new Zefram_Controller_Action_Unit_Model($this, array(
            'modelName' => $this->_modelName,
            'mode' => Zefram_Controller_Action_Unit_Model::CREATE,
        ));
        return $action->run();
    }

    public function editAction()
    {
        $action = new Zefram_Controller_Action_Unit_Model($this, array(
            'modelName' => $this->_modelName,
            'mode' => Zefram_Controller_Action_Unit_Model::UPDATE,
        ));
        return $action->run();
    }
}
