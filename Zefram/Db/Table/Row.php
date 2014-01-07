<?php

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
    public function __construct(array $config = array())
    {
        if (!isset($config['table']) || !$config['table'] instanceof Zend_Db_Table_Abstract) {
            if ($this->_tableClass !== null) {
                $config['table'] = $this->_getTableFromString($this->_tableClass);
            } else {
                throw new Zend_Db_Table_Row_Exception('Table not provided');
            }
        }

        $this->_cols = array_flip($config['table']->info(Zend_Db_Table_Abstract::COLS));

        parent::__construct($config);
    }

    /**
     * @param  string $columnName
     * @return bool
     */
    public function hasColumn($columnName)
    {
        return $this->_hasColumn($this->_transformColumn($columnName));
    }

    /**
     * For internal use, contrary to {@see hasColumn()} it operates on the
     * transformed column name.
     *
     * @param  string $transformedColumnName
     * @return bool
     */
    protected function _hasColumn($transformedColumnName)
    {
        return isset($this->_cols[$transformedColumnName]);
    }

    /**
     * Is reference to parent row identified by rule name defined in the
     * parent table.
     *
     * @param  string $ruleName
     * @param  bool $throw
     * @return bool
     * @throws Exception
     */
    public function hasReference($ruleName, $throw = true)
    {
        try {
            $ruleName = (string) $ruleName;
            $referenceMap = $this->_getTable()->info(Zend_Db_Table_Abstract::REFERENCE_MAP);
            return isset($referenceMap[$ruleName]);

        } catch (Exception $e) {
            if ($throw) {
                throw $e;
            }
        }

        return false;
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
     * Gets the Zend_Db_Adapter_Abstract from the table this row is
     * connected to.
     *
     * @return Zend_Db_Adapter_Abstract
     * @throws Zend_Db_Table_Row_Exception
     */
    public function getAdapter()
    {
        return $this->_getTable()->getAdapter();
    }

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
     * Fetch parent row identified by a given rule name.
     *
     * @param  string $ruleName
     * @return mixed
     */
    protected function _fetchReference($ruleName)
    {
        $ruleName = (string) $ruleName;

        // already have required row or already know that it does not exist
        if (array_key_exists($ruleName, $this->_referencedRows)) {
            return $this->_referencedRows[$ruleName];
        }

        // fetch referenced parent row from the database
        $map = $this->_getTable()->info(Zend_Db_Table_Abstract::REFERENCE_MAP);
        if (isset($map[$ruleName])) {
            $ref = $map[$ruleName];
            $cols = (array) $ref[Zend_Db_Table_Abstract::COLUMNS];

            $this->_ensureLoaded($cols);

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
                $row = $this->findParentRow($ref['refTableClass'], $ruleName);
            }

            // if no referenced row was fetched and there was any non-NULL
            // column involved, report a referential integrity violation
            if (empty($row) && !$emptyForeignKey) {
                throw new Zefram_Db_Table_Row_Exception_ReferentialIntegrityViolation(sprintf(
                    'Row referenced by rule "%s" defined in Table "%s" not found',
                    $ruleName, get_class($this->_getTable())
                ));
            }

            return $this->_referencedRows[$ruleName] = $row;
        }

        throw new Zend_Db_Table_Row_Exception(sprintf(
            'No reference identified by rule "%s" defined in the parent Table',
            $ruleName
        ));
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
        if ($this->hasReference($key, false)) {
            return $this->_fetchReference($key);
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
            } else {
                throw new Zend_Db_Table_Row_Exception(sprintf(
                    'Specified column "%s" is not in the row', $columnName
                ));
            }
        }
 
        $origData = $this->_data[$columnName];

        if ($origData != $value) {
            $this->_data[$columnName] = $value;
            $this->_modifiedFields[$columnName] = true;
        }
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
        return $this->hasColumn($columnName) || $this->hasReference($columnName, false);
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return array_merge(parent::__sleep(), array('_cols'));
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

        foreach ($data as $columnName => $value) {
            $this->__set($columnName, $value);
        }

        return $this;
    }

    /**
     * Refresh columns from the database and storage.
     *
     * This method gets called after each successful write to the database
     * by the {@see Zend_Db_Table_Row_Abstract::save()} method.
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
     * Whenever possible this method fetches row using find() method
     * rather fetchRow(), so that identity map can be utilized (if exists).
     *
     * @param  string|Zend_Db_Table_Abstract $parentTable
     * @param  string $ruleKey OPTIONAL
     * @param  Zend_Db_Table_Select $select OPTIONAL
     * @return Zend_Db_Table_Row_Abstract
     */
    public function findParentRow($parentTable, $ruleKey = null, Zend_Db_Table_Select $select = null)
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

            // mapping between local columns and parent table columns
            $columnMapping = array_combine(
                $rule[Zend_Db_Table_Abstract::REF_COLUMNS],
                $rule[Zend_Db_Table_Abstract::COLUMNS]
            );
            if (false === $columnMapping) {
                throw new Zefram_Db_Table_Row_InvalidArgumentException(sprintf(
                    'Invalid column cardinality in rule "%s"', $ruleKey
                ));
            }

            // if local columns compose complete primary key in the parent
            // table (as should be the case in most situations) use find()
            // to retrieve the parent row so that an identity map (if exists)
            // may be utilized
            $parentPrimaryKey = array();

            foreach ($columnMapping as $refColumn => $column) {
                // access column via __get rather than _data to utilize lazy
                // loading when neccessary
                $parentPrimaryKey[$refColumn] = $this->{$column};
            }

            if (count($parentTable->info(Zend_Db_Table_Abstract::PRIMARY)) === count($parentPrimaryKey)) {
                return $parentTable->find($parentPrimaryKey)->current();
            }
        }

        return parent::findParentRow($parentTable, $ruleKey, $select);
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
            throw new Zend_Db_Table_Row_Exception('Cannot retrieve Table instance from a disconnected Row');
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
