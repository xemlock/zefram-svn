<?php

/**
 * Features Zend_Db_Table_Row does not provide:
 * 1. Methods to determine the state of row: whether it is modified (isModified)
 *    or to get the list of modified columns (getModified) or if it stored in
 *    the database (isStored).
 * 2. Only assignments of different values are recognized of modifications, i.e.
 *    setting column value to an identical value does not count as modification
 * 3. Table factory depends on table instance row is attached to, no static
 *    method of Zend_Db_Table_Abstract is directly called.
 * 4. Ability to get or set referenced parent rows identified by a rule key.
 *    Such rows are available for future use.
 * 5. Columns can be automatically loaded on demand.
 *
 * 2014-04-15
 *          - support for setting referenced rows by assignment to their
 *            corresponding ruleKeys
 *          - fixed fetching referenced rows when values of referencing column
 *            are changed
 */
class Zefram_Db_Table_Row extends Zend_Db_Table_Row
{
    /**
     * @var string
     */
    protected $_tableClass = 'Zefram_Db_Table';

    /**
     * Available row columns. For usability reasons column names are stored
     * as keys, array values are ignored.
     *
     * @var array
     */
    protected $_cols;

    /**
     * @var array
     */
    protected $_referencedRows = array();

    /**
     * Constructor. See {@see Zend_Db_Table_Row_Abstract::__construct()} for
     * more details.
     *
     * @param  array $config OPTIONAL
     * @return void
     * @throws Zend_Db_Table_Row_Exception
     */
    public function __construct(array $config = array()) // {{{
    {
        if (!isset($config['table']) || !$config['table'] instanceof Zend_Db_Table_Abstract) {
            if ($this->_tableClass !== null) {
                $config['table'] = $this->_getTableFromString($this->_tableClass);
            } else {
                throw new Zend_Db_Table_Row_Exception('Table not provided');
            }
        }

        parent::__construct($config);
        $this->_setupCols();
    } // }}}

    public function setTable(Zend_Db_Table_Abstract $table = null) // {{{
    {
        $result = parent::setTable($table);
        $this->_setupCols();

        return $result;
    } // }}}

    protected function _setupCols() // {{{
    {
        $table = $this->_getTable();

        if (null === $table) {
            $this->_cols = null;
        } else {
            $this->_cols = array_flip($table->info(Zend_Db_Table_Abstract::COLS));
        }
    } // }}}

    /**
     * @param  string $columnName
     * @return bool
     */
    public function hasColumn($columnName) // {{{
    {
        return $this->_hasColumn($this->_transformColumn($columnName));
    } // }}}

    /**
     * For internal use, contrary to {@see hasColumn()} it operates on an
     * already transformed column name.
     *
     * @param  string $transformedColumnName
     * @return bool
     */
    protected function _hasColumn($transformedColumnName) // {{{
    {
        return isset($this->_cols[$transformedColumnName]);
    } // }}}

    /**
     * Is this row stored in the database.
     *
     * @return bool
     */
    public function isStored() // {{{
    {
        return !empty($this->_cleanData);
    } // }}}

    /**
     * Does this row have modified fields, or has a specific field been
     * modified?
     *
     * @param  string $columnName OPTIONAL
     * @return bool
     */
    public function isModified($columnName = null) // {{{
    {
        if (null === $columnName) {
            return 0 < count($this->_modifiedFields);
        }

        $columnName = $this->_transformColumn($columnName);
        return isset($this->_modifiedFields[$columnName]);
    } // }}}

    /**
     * Retrieve an array of modified fields and associated values.
     *
     * @return array
     */
    public function getModified() // {{{
    {
        $modified = array();
        foreach ($this->_modifiedFields as $columnName => $value) {
            $modified[$columnName] = $this->_data[$columnName];
        }
        return $modified;
    } // }}}

    /**
     * Gets the Zend_Db_Adapter_Abstract from the table this row is
     * connected to.
     *
     * @return Zend_Db_Adapter_Abstract
     * @throws Zend_Db_Table_Row_Exception
     */
    public function getAdapter() // {{{
    {
        return $this->_getTable()->getAdapter();
    } // }}}

    /**
     * Fetches value for given columns, which effectively re-initializes
     * values of this columns.
     *
     * @param  string|array $transformedColumnNames
     * @return mixed
     * @throws Zend_Db_Table_Row_Exception
     */
    protected function _fetchColumns($transformedColumnNames)
    {
        $table = $this->_getTable();
        $db = $table->getAdapter();

        $value = null;

        $select = $db->select();
        $select->from(
            $table->info(Zend_Db_Table_Abstract::NAME),
            (array) $transformedColumnNames
        );

        foreach ($this->_getWhereQuery(false) as $cond) {
            $select->where($cond);
        }

        foreach ($db->fetchRow($select) as $column => $value) {
            $this->_data[$column] = $value;
            $this->_cleanData[$column] = $value;
            unset($this->_modifiedFields[$column]);
        }

        // return the last fetched value
        return $value;
    }

    /**
     * Is value for given column present.
     *
     * @param  string $transformedColumnName
     * @return bool
     */
    protected function _isColumnLoaded($transformedColumnName)
    {
        return array_key_exists($transformedColumnName, $this->_data);
    }

    /**
     * Ensure all values of given columns are present.
     *
     * @param  string|array $transformedColumnNames
     * @return void
     */
    protected function _ensureLoaded($transformedColumnNames)
    {
        $missingCols = null; // lazy array initialization

        foreach ((array) $transformedColumnNames as $col) {
            // columns in the reference map are expected to be already
            // transformed
            if (!$this->_isColumnLoaded($col)) {
                $missingCols[] = $col;
            }
        }

        if ($missingCols) {
            $this->_fetchColumns($missingCols);
        }
    }

    /**
     * Is reference to parent row identified by rule name defined in the
     * parent table.
     *
     * @param  string $ruleKey
     * @return bool
     * @throws Exception
     */
    public function hasReference($ruleKey) // {{{
    {
        try {
            return (bool) $this->_getReference($ruleKey);
        } catch (Exception $e) {
        }
        return false;
    } // }}}

    /**
     * Get reference rule matching the given key.
     *
     * @param  string $ruleKey
     * @return array
     * @throws Zend_Db_Table_Row_Exception
     */
    protected function _getReference($ruleKey) // {{{
    {
        $ruleKey = (string) $ruleKey;
        $referenceMap = $this->_getTable()->info(Zend_Db_Table_Abstract::REFERENCE_MAP);

        if (isset($referenceMap[$ruleKey])) {
            return $referenceMap[$ruleKey];
        }

        throw new Zend_Db_Table_Row_Exception(sprintf(
            'No reference identified by rule "%s" defined in table %s',
            $ruleKey, get_class($this->_getTable())
        ));
    } // }}}

    /**
     * @param  string|array $rule
     * @return array
     * @throws Zefram_Db_Table_Row_InvalidArgumentException
     */
    protected function _getReferenceColumnMap($rule) // {{{
    {
        if (!is_array($rule)) {
            $rule = $this->_getReference($rule);
        }

        $columnMap = array_combine(
            (array) $rule[Zend_Db_Table_Abstract::COLUMNS],
            (array) $rule[Zend_Db_Table_Abstract::REF_COLUMNS]
        );

        if (false === $columnMap) {
            throw new Zefram_Db_Table_Row_InvalidArgumentException(sprintf(
                "Reference to table %s has invalid column cardinality",
                $rule[Zend_Db_Table_Abstract::REF_TABLE_CLASS]
            ));
        }

        return $columnMap;
    } // }}}

    protected function _normalizeValue($value)
    {
        if (is_float($value) || is_int($value) || is_null($value)) {
            return $value;
        }
        $value = (string) $value;
        if (ctype_digit($value)) {
            $numericValue = (float) $value;
            if ((string) $numericValue === $value) {
                $value = $numericValue;
            }
        }
        return $value;
    }

    /**
     * @var array
     */
    protected $_referenceKeyCache;

    /**
     * @param  string $ruleKey
     * @return string
     */
    protected function _getReferenceKey($ruleKey)
    {
        if (empty($this->_referenceKeyCache[$ruleKey])) {
            $rule = $this->_getReference($ruleKey);

            $cols = (array) $rule[Zend_Db_Table_Abstract::COLUMNS];
            $this->_ensureLoaded($cols);

            $temp = array();
            foreach ($cols as $column) {
                $temp[$column] = $this->_normalizeValue($this->{$column});
            }

            // lazy array initialization
            $this->_referenceKeyCache[$ruleKey] = $ruleKey . '@' . serialize($temp);
        }
        return $this->_referenceKeyCache[$ruleKey];
    }

    /**
     * Fetch parent row identified by a given rule name.
     *
     * @param  string $ruleKey
     * @return mixed
     */
    protected function _fetchReferencedRow($ruleKey) // {{{
    {
        $ruleKey = (string) $ruleKey;
    
        // we must store values of referencing columns and the result they
        // correspond to (a referenced row or null), hence the computed
        // reference key
        $refKey = $this->_getReferenceKey($ruleKey);

        // check if row referenced by given rule is already present in the
        // _referencedRows collection
        if (array_key_exists($refKey, $this->_referencedRows)) {
            return $this->_referencedRows[$refKey];
        }

        // fetch referenced parent row from the database
        $rule = $this->_getReference($ruleKey);
        $cols = (array) $rule[Zend_Db_Table_Abstract::COLUMNS];

        // if all values of the foreign key are NULL, assume that there
        // is no parent row
        $emptyForeignKey = true;

        foreach ($cols as $col) {
            if (isset($this->_data[$col])) {
                $emptyForeignKey = false;
                break;
            }
        }

        if ($emptyForeignKey) {
            $row = null;
        } else {
            $row = $this->findParentRow(
                $rule[Zend_Db_Table_Abstract::REF_TABLE_CLASS],
                $ruleKey
            );
        }

        // if no referenced row was fetched and there was any non-NULL
        // column involved, report a referential integrity violation
        if (empty($row) && !$emptyForeignKey) {
            throw new Zefram_Db_Table_Row_Exception_ReferentialIntegrityViolation(sprintf(
                'Row referenced by rule "%s" defined in Table "%s" not found',
                $ruleKey,
                get_class($this->_getTable())
            ));
        }

        $this->_referencedRows[$refKey] = $row;

        if ($row instanceof Zend_Db_Table_Row_Abstract) {
            return $row;
        }
        return null;
    } // }}}

    /**
     * @param  string $ruleKey
     * @param  Zend_Db_Table_Row_Abstract|null $row
     * @throws Zend_Db_Table_Row_Exception
     * @return void
     */
    protected function _setReferencedRow($ruleKey, $row = null) // {{{
    {
        if (null === $row) {
            // nullify columns that are referencing previous parent object
            // and do not belong to the Primary Key
            $refKey = $this->_getReferenceKey($ruleKey);
            $primary = array_flip((array) $this->_primary);
            foreach ($this->_getReferenceColumnMap($rule) as $column => $refColumn) {
                if (!isset($primary[$column])) {
                    $this->{$column} = null;
                }
            }
            unset($this->_referencedRows[$refKey]);
            return;
        }

        $rule = $this->_getReference($ruleKey);
        $refTable = $this->_getTableFromString($rule[Zend_Db_Table_Abstract::REF_TABLE_CLASS]);
        $rowClass = $refTable->getRowClass();

        if (!$row instanceof $rowClass) {
            throw new Zend_Db_Table_Row_Exception(sprintf(
                "Row referenced by rule '%s' must be an instance of %s",
                $ruleKey,
                $refTable->getRowClass()
            ));
        }

        // update columns in the current row referencing the newly assigned one
        foreach ($this->_getReferenceColumnMap($rule) as $column => $refColumn) {
            $this->{$column} = $row->{$refColumn};
        }

        // referencing columns have changed, compute new reference key
        $refKey = $this->_getReferenceKey($ruleKey);
        $this->_referencedRows[$refKey] = $row;
    } // }}}

    /**
     * Save all modified or not stored referenced rows.
     *
     * This method is called by {@link save()} before saving current row.
     *
     * @return void
     */
    protected function _saveReferencedRows() // {{{
    {
        foreach ($this->_referencedRows as $ruleKey => $row) {
            // any encountered empty referenced rows are removed from storage
            if (!$row instanceof Zend_Db_Table_Row_Abstract) {
                unset($this->_referencedRows[$ruleKey]);
            }
            if ($row === $this) {
                continue;
            }

            // row is either modified or not yet stored in the database
            $isStored = count($row->_cleanData);
            $isModified = count($row->_modifiedFields);

            if ($isModified || !$isStored) {
                try {
                    throw new Exception('Referenced rows must be explicitly saved');
                } catch (Exception $e) {
                    $fh = fopen(Zefram_Os::getTempDir() . '/db_table_row.log', 'a+');
                    fprintf($fh, "%s\n\n", $e->getTraceAsString());
                    fclose($fh);
                }

                $row->save();

                // referenced row was not stored in the database, and as such
                // its Primary Key values might have been undefined
                if (!$isStored) {
                    foreach ($this->_getReferenceColumnMap($ruleKey) as $column => $refColumn) {
                        $this->{$column} = $row->{$refColumn};
                    }
                }
            }
        }
    } // }}}

    /**
     * Retrieve row field value.
     *
     * If the field name starts with an uppercase and a reference rule with
     * the same name exists, the row referenced by this rule is fetched from
     * the database and stored for later use.
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
        $columnName = $this->_transformColumn($key);

        // column value already available, return it
        if ($this->_isColumnLoaded($columnName)) {
            return $this->_data[$columnName];
        }

        // lazy column loading
        if ($this->_hasColumn($columnName)) {
            return $this->_fetchColumns($columnName);
        }

        // reference loading
        if ($this->hasReference($key)) {
            return $this->_fetchReferencedRow($key);
        }

        throw new Zend_Db_Table_Row_Exception(sprintf(
            'Specified column "%s" is not in the row', $columnName
        ));
    }

    /**
     * Does not mark unchanged values as modified. Allows to set values for
     * fields which was not yet fetched from the database.
     */
    public function __set($columnName, $value)
    {
        $columnName = $this->_transformColumn($columnName);

        if (!array_key_exists($columnName, $this->_data)) {
            if ($this->_hasColumn($columnName)) {
                $this->_data[$columnName] = $value;
                $this->_modifiedFields[$columnName] = true;

            } elseif ($this->hasReference($columnName)) {
                $this->_setReferencedRow($columnName, $value);

            } else {
                throw new Zend_Db_Table_Row_Exception(sprintf(
                    'Specified column "%s" is not in the row', $columnName
                ));
            }

            return;
        }
 
        $origData = $this->_data[$columnName];

        // when comparing with previous value check if both types match
        // to avoid undesired behavior caused by type convergence, i.e.
        // NULL == 0, NULL == "", 0 == ""
        if ($origData !== $value) {
            $this->_data[$columnName] = $value;
            $this->_modifiedFields[$columnName] = true;
        }

        // force recalculation of referenced rows identifiers
        $this->_referenceKeyCache = null;
    }

    /**
     * Test existence of field or reference. For more specific test
     * use {@see hasColumn()} or {@see hasReference()} methods.
     *
     * @param  string $columnName
     * @return bool
     */
    public function __isset($columnName)
    {
        return $this->hasColumn($columnName) || $this->hasReference($columnName);
    }

    public function __unset($columnName)
    {
        if (!$this->hasColumn($columnName) && $this->hasReference($columnName)) {
            unset($this->_referencedRows[$columnName]);
            return $this;
        }
        return parent::__unset($columnName);
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return array_merge(parent::__sleep(), array('_cols'));
    }

    protected function _refresh()
    {
        $this->_referenceKeyCache = null;
        return parent::_refresh();
    }

    /**
     * Sets all data in the row from an array.
     *
     * @param  array $data
     * @return Zend_Db_Table_Row_Abstract
     */
    public function setFromArray(array $data)
    {
        $data = array_intersect_key($data, $this->_cols);

        foreach ($data as $columnname => $value) {
            $this->__set($columnname, $value);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function save()
    {
        $this->_saveReferencedRows();
        // no! this may lead to infinite recursion if referential integrity
        // actions are performed!!! Referenced rows must be saved explicitly

        $result = parent::save();
        $this->_getTable()->addToIdentityMap($this);

        return $result;
    }

    /**
     * @return int
     */
    public function delete()
    {
        // Prior to deletion, remember primary key values to be used when
        // removing this row from the identity map.
        $primaryKey = $this->_getPrimaryKey(false);
        $result = parent::delete();

        if ($result) {
            $this->_getTable()->removeFromIdentityMap($primaryKey);
        }

        return $result;
    }

    /**
     * @param  bool $includeReferencedRows deprecated
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
     * Whenever possible this method fetches row using find() method
     * rather fetchRow(), so that identity map can be utilized (if exists).
     *
     * @param  string|Zend_Db_Table_Abstract $parentTable
     * @param  string $ruleKey OPTIONAL
     * @param  Zend_Db_Table_Select $select OPTIONAL
     * @return Zend_Db_Table_Row_Abstract
     */
    public function findParentRow($parentTable, $ruleKey = null, Zend_Db_Table_Select $select = null) // {{{
    {
        $db = $this->_getTable()->getAdapter();

        if (is_string($parentTable)) {
            $parentTable = $this->_getTableFromString($parentTable);
        }

        if (!$parentTable instanceof Zend_Db_Table_Abstract) {
            throw new Zend_Db_Table_Row_Exception(sprintf(
                'Parent table must be a Zend_Db_Table_Abstract, but it is %s',
                is_object($parentTable) ? get_class($parentTable) : gettype($parentTable)
            ));
        }

        // no select, try to fetch referenced row via find() called on the
        // parent table
        if (null === $select) {
            $rule = $this->_prepareReference($this->_getTable(), $parentTable, $ruleKey);

            // mapping between local columns and columns in referenced table
            $columnMap = $this->_getReferenceColumnMap($rule);

            // if local columns compose complete primary key in the parent
            // table (as should be the case in most situations) use find()
            // to retrieve the parent row so that an identity map (if exists)
            // may be utilized
            $parentPrimaryKey = array();

            foreach ($columnMap as $column => $refColumn) {
                // access column via __get rather than _data to utilize lazy
                // loading when neccessary
                $parentPrimaryKey[$refColumn] = $this->{$column};
            }

            if (count($parentTable->info(Zend_Db_Table_Abstract::PRIMARY)) === count($parentPrimaryKey)) {
                return $parentTable->find($parentPrimaryKey)->current();
            }
        }

        return parent::findParentRow($parentTable, $ruleKey, $select);
    } // }}}

    /**
     * Retrieve an instance of the table this row is connected to or, if table
     * name given, instantiate a table of a this class.
     *
     * @return Zend_Db_Table_Abstract
     * @throws Zend_Db_Table_Row_Exception
     */
    protected function _getTable($tableName = null) // {{{
    {
        if (!$this->_connected || !$this->_table) {
            throw new Zend_Db_Table_Row_Exception('Cannot retrieve Table instance from a disconnected Row');
        }
        if (null === $tableName) {
            return $this->_table;
        }

        try {
            throw new Exception;
        } catch (Exception $e) {
            $trace = $e->getTrace();
            $last = reset($trace);
            trigger_error(sprintf(
                'Calling %s() with table name parameter is deprecated. Called in %s on line %d',
                __METHOD__, $last['file'], $last['line']
            ), E_USER_NOTICE);
        }

        return $this->_table->_getTableFromString($tableName);
    } // }}}

    /**
     * Instantiate a table of a given class using connected table as
     * a factory.
     *
     * @param  string $tableName
     * @return Zend_Db_Table_Abstract
     */
    protected function _getTableFromString($tableName) // {{{
    {
        $table = $this->_getTable();

        if ($table instanceof Zefram_Db_Table) {
            return $table->_getTableFromString($tableName);
        }

        return parent::_getTableFromString($tableName);
    } // }}}
}
