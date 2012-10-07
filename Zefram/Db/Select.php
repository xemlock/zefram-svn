<?php

class Zefram_Db_Select extends Zend_Db_Select
{
    public function fetchRow($fetchMode = null)
    {
        return $this->query($fetchMode)->fetch();
    }

    public function fetchAll($fetchMode = null)
    {
        return $this->query($fetchMode)->fetchAll();
    }
}
