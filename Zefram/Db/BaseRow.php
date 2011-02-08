<?php

class ZUtils_Db_BaseRow extends Zend_Db_Table_Row {
    protected $_nullifyEmpty = true;

    protected function nullifyEmptyNullableCols() {
        $info = $this->_getTable()->info();
        $metadata = $info[Zend_Db_Table_Abstract::METADATA];
        foreach ($metadata as $col => $spec) {
            if ($spec['NULLABLE'] && !strlen($this->$col)) {
                $this->$col = null;
            }
        }
    }

    protected function _doNullify() {
        if ($this->_nullifyEmpty) $this->nullifyEmptyNullableCols();
    }

    protected function _insert() {
        $this->_doNullify();
    }

    protected function _update() {
        $this->_doNullify();
    }
}

// vim: et sw=4 fdm=marker
