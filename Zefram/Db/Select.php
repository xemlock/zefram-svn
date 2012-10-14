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

    /**
     * Adds a WHERE conditions to the query by AND.
     *
     * @param array|string $cond
     *     The WHERE condition. If array, treat its elements the same way as
     *     $where parameter in {@see Zend_Db_Table_Abstract::fetchAll()}.
     * @param mixed $value
     *     OPTIONAL The value to quote into the condition.
     * @param int $type
     *     OPTIONAL The type of the given value
     * @return Zefram_Db_Select This object.
     */
    public function where($cond, $value = null, $type = null)
    {
        if (is_array($cond)) {
            foreach ($where as $key => $val) {
                if (is_int($key)) {
                    // $val is the full condition
                    parent::where($val);
                } else {
                    // $key is the condition with placeholder,
                    // and $val is quoted into the condition
                    parent::where($key, $val);
                }
            }
        } else {
            parent::where($cond, $value, $type);
        }

        return $this;
    }
}
