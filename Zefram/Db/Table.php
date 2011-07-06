<?php

class Zefram_Db_Table extends Zend_Db_Table_Abstract
{
    // does anybody know why these are missing in Zend_Db
    // info() is extremely inconvenient
    public function getName()
    {
        return $this->_name;
    }

    public function getSchema()
    {
        return $this->_schema;
    }

    // numeric $where value is treated as pk = value condition
    // (works just like find but returns single row on success
    // rather than rowset).
    public function fetchRow($where = null, $order = null)
    {
        if (is_scalar($where) && ctype_digit($where)) {
            // numeric primary key
            $primary = $this->info(Zend_Db_Table_Abstract::PRIMARY);
            if (count($primary) == 1) {
                // only scalar primary key
                $primary = reset($primary);
                $where = array($primary . ' = ?' => $where);
            }
        }
        return parent::fetchRow($where, $order);
    }
}
