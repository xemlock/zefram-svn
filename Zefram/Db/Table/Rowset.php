<?php

class Zefram_Db_Table_Rowset extends Zend_Db_Table_Rowset
{
    protected function _loadAndReturnRow($position)
    {
        $row = parent::_loadAndReturnRow($position);
        $table = $this->getTable();

        if ($row && ($table instanceof Zefram_Db_Table)) {
            $table->addToIdentityMap($row);
        }

        return $row;
    }
}
