<?php

abstract class Zefram_Db_Driver_Abstract
{
    protected $_modelName;

    public function __construct($modelName) 
    {
        $this->_modelName = $modelName;
    }

    public function getName()
    {
        return $this->_modelName;
    }

    abstract public function getModel();
    abstract public function getSpecs(); // FIXME better name is getColumns
    abstract public function getIdentifier();
    abstract public function getConnection();

//    abstract public function getDependentRow($record, $dependentTable);
    abstract public function getParent($record = null);

    abstract public function find($id);
    abstract public function createRow();
    abstract public function populateRecord($record, $data);

    abstract public static function getDefaultConnection();
}
