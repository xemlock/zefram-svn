<?php

class Zefram_Db_Table_Row extends Zend_Db_Table_Row
{
    /**
     * @var string
     */
    protected $_tableClass = 'Zefram_Db_Table';

    /**
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
     * @param  array $config OPTIONAL Array of user-specified config options.
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

        $this->_cols = $config['table']->info(Zend_Db_Table_Abstract::COLS);

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
        return in_array($transformedColumnName, $this->_cols, true);
    }

    /**
     * @param  string $referenceName
     * @param  bool $throw
     * @return bool
     * @throws Exception
     */
    public function hasReference($referenceName, $throw = true)
    {
        try {
            $referenceName = (string) $referenceName;
            $referenceMap = $this->_getTable()->info(Zend_Db_Table_Abstract::REFERENCE_MAP);
            return isset($referenceMap[$referenceName]);

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
     * @param  string $transformedColumnName
     * @return bool
     */
    protected function _isColumnLoaded($transformedColumnName)
    {
        return array_key_exists($transformedColumnName, $this->_data);
    }

    /**
     * @param  string $referenceName
     * @return mixed
     */
    protected function _fetchReference($referenceName)
    {
        $referenceName = (string) $referenceName;

        // already have required row or already know that it does not exist
        if (array_key_exists($referenceName, $this->_referencedRows)) {
            return $this->_referencedRows[$referenceName];
        }

        // fetch referenced parent row from the database
        $map = $this->_getTable()->info(Zend_Db_Table_Abstract::REFERENCE_MAP);
        if (isset($map[$referenceName])) {
            $ref = $map[$referenceName];

            // ensure all required columns are already fetched
            $cols = (array) $ref[Zend_Db_Table_Abstract::COLUMNS];
            $missingCols = null; // lazy array initialization
            foreach ($cols as $col) {
                // columns in the reference map are expected to be already
                // transformed
                if (!$this->_isColumnLoaded($col)) {
                    $missingCols[] = $col;
                }
            }
            if ($missingCols) {
                $this->_fetchColumns($missingCols);
            }

            $row = $this->findParentRow($ref['refTableClass'], $referenceName);
            if (empty($row)) {
                // if no row was fetched, check if all referencing columns are
                // NULL, otherwise throw referential integrity exception
                $allNullCols = true;
                foreach ($cols as $col) {
                    if (isset($this->_data[$col])) {
                        $allNullCols = false;
                        break;
                    }
                }
                if (!$allNullCols) {
                    // prepare meaningful information about searched row
                    throw new Zefram_Db_Table_Row_Exception_ReferentialIntegrityViolation(sprintf(
                        'Referenced row not found: %s (%s)',
                        get_class($this->_getTable()), $referenceName
                    ));
                }
            }

            return $this->_referencedRows[$referenceName] = $row;
        }

        throw new Exception('No reference by that name is defined in the parent Table');
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
