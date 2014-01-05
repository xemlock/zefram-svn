<?php

class Zefram_Db_Table_Row extends Zend_Db_Table_Row
{
    protected $_tableClass = 'Zefram_Db_Table';

    /**
     * @var array
     */
    protected $_referencedRows = array();

    /**
     * @param  string $columnName
     * @return bool
     */
    public function hasColumn($columnName)
    {
        $columnName = $this->_transformColumn($columnName);
        return array_key_exists($columnName, $this->_data);
    }

    /**
     * @param  string $referenceName
     * @return bool
     */
    public function hasReference($referenceName)
    {
        $referenceName = (string) $referenceName;
        $referenceMap = $this->_getTable()->info(Zend_Db_Table_Abstract::REFERENCE_MAP);
        return isset($referenceMap[$referenceName]);
    }

    /**
     * Is this row stored in the database.
     *
     * @return bool
     */
    public function isStored()
    {
        return !empty($this->_cleanData);
    }

    /**
     * Does this row have modified fields, or has a specific field been
     * modified?
     *
     * @param  string $columnName OPTIONAL
     * @return bool
     */
    public function isModified($columnName = null)
    {
        if (null === $columnName) {
            return 0 < count($this->_modifiedFields);
        }

        $columnName = $this->_transformColumn($columnName);
        return isset($this->_modifiedFields[$columnName]);
    }

    /**
     * Retrieve an array of modified fields and associated values.
     *
     * @return array
     */
    public function getModified()
    {
        $modified = array();
        foreach ($this->_modifiedFields as $columnName => $value) {
            $modified[$columnName] = $this->_data[$columnName];
        }
        return $modified;
    }

    /**
     * @return array
     */
    public function getModifiedFields()
    {
        return $this->_modifiedFields;
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
        return $this->_getTable()->getAdapter();
    }

    /**
     * Fetches value for a given column, which effectively re-initializes
     * this column's value.
     *
     * @param  $columnName
     * @return mixed
     */
    protected function _fetchColumn($columnName)
    {
        $table = $this->_getTable();
        $db = $this->getAdapter();

        $columnName = $this->_transformColumn($columnName);

        $select = $db->select();
        $select->from($table->info(Zend_Db_Table_Abstract::NAME), $columnName);

        // Primary Key must be set in order to fetch selected column value,
        // get stored values, not dirty ones
        foreach ($this->_getPrimaryKey(false) as $col => $value) {
            $select->where($db->quoteIdentifier($col) . ' = ?', $value);
        }

        $columnValue = $db->fetchCol($select);

        $this->_data[$columnName] = $this->_cleanData[$columnName] = $columnValue;
        unset($this->_modifiedFields[$columnName]);

        return $columnValue;
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
        // lazy column loading
        if (!$this->hasColumn($key) && $this->_table) {
            foreach ($this->_table->info(Zend_Db_Table_Abstract::COLS) as $col) {
                if (!strcasecmp($col, $key)) {
                    return $this->_fetchColumn($key);
                }
            }
        }

        // reference loading
        if (!$this->hasColumn($key) && $this->hasReference($key)) {
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
     * Does not mark unchanged values as modified.
     */
    public function __set($columnName, $value)
    {
        $columnName = $this->_transformColumn($columnName);
        if (!array_key_exists($columnName, $this->_data)) {
            throw new Zend_Db_Table_Row_Exception(sprintf(
                'Specified column "%s" is not in the row', $columnName
            ));
        }
 
        $origData = $this->_data[$columnName];
        if ($origData != $value) {
            $this->_data[$columnName] = $value;
            $this->_modifiedFields[$columnName] = true;
        }
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
     * @return mixed
     */
    public function save()
    {
        $result = parent::save();
        $this->_getTable()->addToIdentityMap($this);

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
            $this->_getTable()->removeFromIdentityMap($data);
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
                if ($row instanceof Zend_Db_Table_Row_Abstract) {
                    $array[$key] = $row->toArray($includeReferencedRows);
                }
            }
        }

        return $array;
    }

    /**
     * Retrieve an instance of the table this row is connected to.
     *
     * @return Zend_Db_Table_Row_Abstract
     * @throws Zend_Db_Table_Row_Exception
     */
    protected function _getTable()
    {
        if (!$this->_connected || !$this->_table) {
            throw new Zend_Db_Table_Row_Exception('Cannot get table from a disconnected Row');
        }
        return $this->_table;
    }

    /**
     * Instantiate a table of a given class.
     *
     * @param  string $tableName
     * @return Zend_Db_Table_Abstract
     */
    protected function _getTableFromString($tableName)
    {
        return Zefram_Db::getTable($tableName, $this->getAdapter(), false);
    }
}
