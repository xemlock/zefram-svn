<?php

class Zefram_Db_Table_Row extends Zend_Db_Table_Row
{
    protected $_tableClass = 'Zefram_Db_Table';

    public function getAdapter()
    {
        $table = $this->getTable();

        if (empty($table)) {
            throw new Zend_Db_Table_Row_Exception('Row is not connected to a table');
        }

        return $table->getAdapter();
    }
}
