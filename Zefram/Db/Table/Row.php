<?php

class Zefram_Db_Table_Row extends Zend_Db_Table_Row
{
    protected $_tableClass = 'Zefram_Db_Table';
    protected $_referencedRows = array();

    /**
     * Does this row has unsaved field modifications
     *
     * @param string $columnName OPTIONAL check if given column is modified
     */
    public function isDirty($columnName = null)
    {
        if (null === $columnName) {
            return 0 < count($this->_modifiedFields);
        }

        $columnName = $this->_transformColumn($columnName);
        return isset($this->_modifiedFields[$columnName]);
    }

    /**
     * Gets the Zend_Db_Adapter_Abstract used by the table this row
     * is bound to.
     *
     * @return Zend_Db_Adapter_Abstract
     * @throws Zend_Db_Table_Row_Exception
     *     If this row is disconnected.
     */
    public function getAdapter()
    {
        $table = $this->getTable();

        if (empty($table)) {
            throw new Zend_Db_Table_Row_Exception('Row is not connected to a table');
        }

        return $table->getAdapter();
    }

    /**
     * Retrieve row field value.
     *
     * If the field name starts with an uppercase and a reference rule with
     * the same name exists, the row referenced by this rule is fetched from
     * the database ({@see Zefram_Db_Table::findRow()}) and stored for later
     * use.
     *
     * @param string $key
     * @throws Zefram_Db_Table_Row_InvalidArgumentException
     *     Number of columns defined in reference rule does not match the
     *     number of columns in the primary key of the parent table.
     * @throws Zefram_Db_Table_Row_Exception_ReferentialIntegrityViolation
     *     No referenced row was found even though columns containing the
     *     primary key of row in the parent table are marked as NOT NULL.
     */
    public function __get($key)
    {
        if (is_string($key) && ctype_upper(substr($key, 0, 1))) {
            // already have required row or already know that it does not exist
            if (array_key_exists($key, $this->_referencedRows)) {
                return $this->_referencedRows[$key];
            }

            // fetch row from the database
            $map = $this->getTable()->info(Zend_Db_Table_Abstract::REFERENCE_MAP);

            if (isset($map[$key])) {
                $db    = $this->getAdapter();
                $rule  = $map[$key];
                $table = Zefram_Db::getTable($rule[Zend_Db_Table_Abstract::REF_TABLE_CLASS], $db, false);

                // all refColumns must belong to referenced table's primary key
                $columns = array_combine(
                    (array) $rule[Zend_Db_Table_Abstract::REF_COLUMNS],
                    (array) $rule[Zend_Db_Table_Abstract::COLUMNS]
                );

                if (false === $columns) {
                    throw new Zefram_Db_Table_Row_InvalidArgumentException("Invalid column cardinality in rule '$key'");
                }

                $id = array();

                foreach ($columns as $refColumn => $column) {
                    $value = $this->{$column};

                    // no column of a referenced primary key may be NULL
                    if (null === $value) {
                        $this->_referencedRows[$key] = null;
                        return null;
                    }

                    $id[$refColumn] = $value;
                }

                $row = $table->findRow($id);

                if (empty($row)) {
                    // prepare meaningful information about searched row
                    $where = array();
                    foreach ($id as $column => $value) {
                        $where[] = $db->quoteIdentifier($column) . ' = ' . $db->quote($value);
                    }

                    throw new Zefram_Db_Table_Row_Exception_ReferentialIntegrityViolation(sprintf(
                        "Referenced row not found: %s (%s)",
                        $table->getName(), implode(' AND ', $where)
                    ));
                }

                $this->_referencedRows[$key] = $row;

                return $row;
            }
        }

        return parent::__get($key);
    }

    /**
     * Refreshes properties from the database and clears referenced rows
     * storage. This method gets called after each successful write to the
     * database by the {@see Zend_Db_Table_Row_Abstract::save()} method.
     *
     * @return void
     */
    protected function _refresh()
    {
        parent::_refresh();
        $this->_referencedRows = array();
    }

    /**
     * @param string $tableName
     * @return Zend_Db_Table_Abstract
     */
    protected function _getTableFromString($tableName)
    {
        return Zefram_Db::getTable($tableName, $this->getAdapter(), false);
    }

    /**
     * @return mixed
     */
    public function save()
    {
        $result = parent::save();
        $this->getTable()->addToIdentityMap($this);

        return $result;
    }

    /**
     * @return int
     */
    public function delete()
    {
        // before deletion copy all field values to be used when removing
        // this row from the identity map
        $data = $this->_data;
        $result = parent::delete();

        if ($result) {
            $this->getTable()->removeFromIdentityMap($data);
        }

        return $result;
    }

    /**
     * @param  bool $includeReferencedRows
     * @return array
     */
    public function toArray($includeReferencedRows = false)
    {
        $array = parent::toArray();

        if ($includeReferencedRows) {
            foreach ($this->_referencedRows as $key => $row) {
                $array[$key] = $row->toArray($includeReferencedRows);
            }
        }

        return $array;
    }
}
