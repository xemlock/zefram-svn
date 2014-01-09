<?php

class Zefram_Validate_Db_RecordExists extends Zend_Validate_Db_RecordExists
{
    /**
     * @param  string|Zend_Db_Expr $field
     * @return Zefram_Validate_Db_RecordExists
     */
    public function setField($field)
    {
        if (!$field instanceof Zend_Db_Expr) {
            $field = (string) $field;
        }
        $this->_field = $field;
        return $this;
    }
}
