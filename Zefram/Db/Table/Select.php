<?php

class Zefram_Db_Table_Select extends Zend_Db_Table_Select
{
    protected $_indexBy;

    public function indexBy($indexBy = null)
    {
        $this->_indexBy = $indexBy;
        return $this;
    }

    public function fetchRow($fetchMode = null)
    {
        return $this->query($fetchMode)->fetch();
    }

    public function fetchAll($fetchMode = null)
    {
        if (empty($this->_indexBy)) {
            $rows = $this->query($fetchMode)->fetchAll();

        } else {
            // index results by column given by _indexBy property
            $stmt = $this->query($fetchMode);
            $rows = array();
            while ($row = $stmt->fetch()) {
                $rows[$row[$this->_indexBy]] = $row;
            }
        }

        return $rows;
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
            foreach ($cond as $key => $val) {
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
