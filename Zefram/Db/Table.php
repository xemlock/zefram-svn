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
}
