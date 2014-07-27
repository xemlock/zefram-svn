<?php

interface Zefram_Db_Table_FactoryInterface
{
    /**
     * @param  string $name
     * @return Zend_Db_Table_Abstract
     */
    public function getTable($name);
}
