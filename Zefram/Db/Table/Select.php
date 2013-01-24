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
     * @return Zefram_Db_Select
     *     This object
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

    /**
     * Enhancement for {@see Zend_Db_Select::_join()} allowing Zend_Db_Table
     * instances to be passed as the $name parameter.
     *
     * @param null|string $type
     *     Type of join
     * @param array|string|Zend_Db_Expr|Zend_Db_Table $name
     *     Table instance or name
     * @param string $cond
     *     Join on this condition
     * @param array|string $cols
     *     The columns to select from the joined table
     * @param string $schema
     *     OPTIONAL The database name to specify, if any. If $name parameter
     *     is an instance of Zend_Db_Table, schema of this instance is used
     * @return Zefram_Db_Select
     *     This object
     */
    protected function _join($type, $name, $cond, $cols, $schema = null)
    {
        $alias = null;

        if (is_array($name)) {
            // inconsistent behavior across PHP versions, in 4.4.1, and in
            // 5.2.4-5.5.0alpha4 the internal pointers of arrays passed by
            // value to functions are reset. In other versions (4.3.0-4.4.0,
            // 4.4.2-5.2.3) they are not.
            reset($name);

            $alias = key($name);
            $name = current($name);
        }

        if ($name instanceof Zend_Db_Table_Abstract) {
            $schema = $name->info(Zend_Db_Table::SCHEMA);
            $name = $name->info(Zend_Db_Table::NAME);
        }

        if (null !== $alias) {
            $name = array($alias => $name);
        }

        return parent::_join($type, $name, $cond, $cols, $schema);
    }
}
