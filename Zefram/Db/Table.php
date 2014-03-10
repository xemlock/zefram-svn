<?php

class Zefram_Db_Table extends Zend_Db_Table
{
    const FOR_UPDATE = 1;

    /**
     * @var string
     */
    protected $_rowClass = 'Zefram_Db_Table_Row';

    /**
     * @var string
     */
    protected $_rowsetClass = 'Zefram_Db_Table_Rowset';

    /**
     * @var Zefram_Db_TableProvider
     */
    protected $_tableProvider;

    /**
     * Storage for rows fetched by {@link findRow()}.
     * @var array
     */
    protected $_identityMap = array();

    // does anybody know why these are missing in Zend_Db?
    // info() is somewhat inconvenient
    public function getSchema()
    {
        return $this->_schema;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getQualifiedName()
    {
        if ($this->_schema) {
            return $this->_schema . '.' . $this->_name;
        }
        return $this->_name;
    }

    public function getQuotedName()
    {
        return $this->getAdapter()->quoteIdentifier($this->_name);
    }

    /**
     * Proxy to {@see _getCols()}.
     *
     * @return array
     */
    public function getCols()
    {
        return $this->_getCols();
    }

    /**
     * @param  bool $normalized OPTIONAL
     * @return array
     */
    public function getReferenceMap($normalized = false)
    {
        if ($normalized) {
            return $this->_getReferenceMapNormalized();
        }
        return $this->_referenceMap;
    }

    /**
     * Retrieve metadata for the whole table, selected column or a given
     * property of a selected column.
     *
     * Column and property operators are added for convenience when developing
     * in PHP version prior to 5.4 when array dereference on functions was not
     * supported.
     *
     * @param  string $column OPTIONAL
     * @param  string $property OPTIONAL
     * @return array
     */
    public function getMetadata($column = null, $property = null)
    {
        $this->_setupMetadata();

        if (null === $column) {
            return $this->_metadata;
        }

        // column names in table descriptions are case-folded according
        // to database adapter they are bound to, for example see:
        // Zend_Db_Adapter_Pdo_Pgsql::describeTable()

        $column = $this->getAdapter()->foldCase($column);

        if (isset($this->_metadata[$column])) {
            if (null === $property) {
                return $this->_metadata[$column];
            }

            if (isset($this->_metadata[$column][$property])) {
                return $this->_metadata[$column][$property];
            }

            throw new Exception('Invalid column property name');
        }

        throw new Exception('Invalid column name');
    }

    /**
     * Fetches all rows, but returns them as arrays instead of objects.
     * See {@link Zend_Db_Table_Abstract::fetchAll()} for parameter
     * explanation.
     *
     * @return array
     */
    public function fetchAllAsArray($where = null, $order = null, $count = null, $offset = null)
    {
        if (!($where instanceof Zend_Db_Select)) {
            $select = $this->select();

            if (null !== $where) {
                $this->_where($select, $where);
            }
            
            if ($order !== null) {
                $this->_order($select, $order);
            }

            if ($count !== null || $offset !== null) {
                $select->limit($count, $offset);
            }
        } else {
            $select = $where;
        }

        return $this->getAdapter()->fetchAll($select, null, Zend_Db::FETCH_ASSOC);
    }

    /**
     * Fetches one row as array or returns false if no row matches the
     * specified criteria. See {@link Zend_Db_Table_Abstract::fetchRow()}
     * for parameter explanation. 
     *
     * @return array|false
     */
    public function fetchRowAsArray($where = null, $order = null, $offset = null)
    {
        if (!($where instanceof Zend_Db_Select)) {
            $select = $this->select();

            if ($where !== null) {
                $this->_where($select, $where);
            }

            if ($order !== null) {
                $this->_order($select, $order);
            }

            $select->limit(1, is_numeric($offset) ? intval($offset) : null);
        } else {
            $select = $where;
        }

        $row = $this->getAdapter()->fetchRow($select, null, Zend_Db::FETCH_ASSOC);
        return empty($row) ? false : $row;
    }

    /**
     * Count rows matching $where
     *
     * @param string|array $where
     * @return int
     */
    public function count($where = null)
    {
        $select = $this->select();
        $select->from($this->_name, 'COUNT(1)', $this->_schema);

        if (null !== $where) {
            $this->_where($select, $where);
        }

        foreach ($this->getAdapter()->fetchCol($select) as $value) {
            return intval($value);
        }

        return 0;
    }

    /**
     * Proxy to {@see count()}.
     */
    public function countAll($where = null)
    {
        return $this->count($where);
    }

    /**
     * Finds a record by its identifier.
     *
     * @param mixed $id
     *     Database row ID
     * @param int $flags
     *     OPTIONAL flags
     * @return mixed
     *     Zend_Db_Table_Row or false if no result
     * @throws Exception
     *     when incomplete primary key values given or if column does not
     *     belong to primary key
     */
    public function findRow($id)
    {
        if (null === ($row = $this->getFromIdentityMap($id))) {
            $id = $this->_normalizeId($id);
            $db = $this->getAdapter();
            $where = array();

            $select = $this->select();
            $select->limit(1);

            foreach ($id as $column => $value) {
                $select->where(
                    $db->quoteIdentifier($column) . ' = ' . $db->quote($value)
                );
            }

            $row = $this->fetchRow($select);
        }

        return $row;
    }

    /**
     * Fetch row from the database and store it in the identity map.
     * Rows fetched by this method overwrite those stored in identity
     * map. Use findRow() for identity map utilization.
     */
    public function fetchRow($where = null, $order = null, $offset = null)
    {
        $row = parent::fetchRow($where, $order, $offset);
        if ($row) {
            // No! Don't retrieve row from identity map, as fetchRow()
            // is used for refreshing table row.
            // However, we can add this row to identity map, for later use.
            $this->addToIdentityMap($row);
        }
        return $row;
    }

    /**
     * @param  mixed $key The value(s) of the primary keys.
     * @return Zend_Db_Table_Rowset_Abstract
     * @deprecated
     */
    public function findAll()
    {
        $args = func_get_args();

        if (-1 === version_compare(PHP_VERSION, '5.1.2')) {
            // this line segfaults PHP 5.0.0 - 5.0.3
            return call_user_func_array(array('parent', 'find'), $args);
        }

        return call_user_func_array(array($this, 'parent::find'), $args);
    }

    /**
     * Mass-fetch records by their identifiers. Records already present in
     * the identity map will not be fetched again.
     *
     * @param array $ids
     * @param string $indexBy (Optional)
     *     Use field with this name to index rows in the resulting array.
     *     The field used as a row index must be unique.
     * @throws Exception
     * @return array<Zend_Db_Table_Row>
     * @deprecated
     */
    public function findRows($ids, $indexBy = null)
    {
        $rows = array();
        $where = array();

        $primary = $this->info(self::PRIMARY);
        $db = $this->getAdapter();

        foreach ($ids as $id) {
            $id = $this->_normalizeId($id);
            $key = serialize($id);

            if (array_key_exists($key, $this->_identityMap)) {
                $row = $this->_identityMap[$key];
                if ($row) {
                    if ($indexBy) {
                        $rows[$row->{$indexBy}] = $row;
                    } else {
                        $rows[] = $row;
                    }
                }
            } else {
                $subWhere = array();
                foreach ($primary as $column) {
                    if (isset($id[$column])) {
                        $subWhere[] = $db->quoteIdentifier($column) . ' = ' . $db->quote($id[$column]);
                    }
                }
                if (count($subWhere) != count($primary)) {
                    throw new Exception('Incomplete primary key values');
                }
                $where[] = '(' . implode(' AND ', $subWhere) . ')';
            }
        }

        // fetch required rows and add them to the identity map
        if ($where) {
            $where = implode(' OR ', $where);
            foreach ($this->fetchAll($where) as $row) {
                $id = $this->_normalizeId($row);
                $key = serialize($id);

                $this->_identityMap[$key] = $row;
                if ($indexBy) {
                    $rows[$row->{$indexBy}] = $row;
                } else {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    public function findRowAsArray($id)
    {
        $row = $this->findRow($id);

        if ($row) {
            return $row->toArray();
        }

        return false;
    }

    /**
     * Created an instance of a Zend_Db_Select.
     *
     * @param string|array|bool $column
     *     If boolean acts like Zend_Db_Table_Select.
     *     If string, it is used as a name of a column to select from this
     *     table. If array, its first key is used as a table correlation name,
     *     and the values corresponding to this key are used as column names
     *     to be selected.
     * @param string $column,... OPTIONAL
     *     Number of additional columns to select from this table.
     * @return Zefram_Db_Table_Select
     * @deprecated
     */
    public function select($columns = self::SELECT_WITHOUT_FROM_PART)
    {
        $select = new Zefram_Db_Table_Select($this);

        if (self::SELECT_WITHOUT_FROM_PART !== $columns) {
            // substitute true with SQL wildcard to match all columns
            if (self::SELECT_WITH_FROM_PART === $columns) {
                $columns = Zend_Db_Select::SQL_WILDCARD;
            }

            $name = $this->info(self::NAME);

            $columns = (array) $columns;
            $alias = key($columns);

            // Array key can either be an integer or a string.
            // http://php.net/manual/en/language.types.array.php
            if (is_string($alias)) {
                // array(alias => array(column1, ..., columnN)
                $name = array($alias => $name);
                $columns = reset($columns);
            }
            // else array(column1, ..., columnN)

            $select->from($name, $columns, $this->_schema);
        }

        return $select;
    }

    /**
     * Begin transaction on an underlying database adapter.
     *
     * @return Zend_Db_Adapter_Abstract
     * @deprecated
     */
    public function beginTransaction()
    {
        return $this->getAdapter()->beginTransaction();
    }

    /**
     * Commit a transaction.
     *
     * @return Zend_Db_Adapter_Abstract
     * @deprecated
     */
    public function commit()
    {
        return $this->getAdapter()->commit();
    }

    /**
     * Roll back a transaction.
     *
     * @return Zend_Db_Adapter_Abstract
     * @deprecated
     */
    public function rollBack()
    {
        return $this->getAdapter()->rollBack();
    }

    /**
     * @param  mixed $id
     * @return Zefram_Db_Table
     */
    public function removeFromIdentityMap($id)
    {
        // Unsetting an unexistant key from an existing array does not trigger
        // an "Undefined variable" notice. See:
        // http://www.php.net/manual/en/function.unset.php#77310
        $key = serialize($this->_normalizeId($id));
        unset($this->_identityMap[$key]);
        return $this;
    }

    
    public function addToIdentityMap($row)
    {
        if ((null !== $row) && !$row instanceof $this->_rowClass) {
            throw new Exception(sprintf(
                'Non-empty row must be an instance of %s', $this->_rowClass
            ));
        }
        $key = serialize($this->_normalizeId($row));
        $this->_identityMap[$key] = $row ? $row : false;
        return $this;
    }

    public function getFromIdentityMap($id)
    {
        $key = serialize($this->_normalizeId($id));
        if (isset($this->_identityMap[$key])) {
            return $this->_identityMap[$key];
        }
        return null;
    }

    /**
     * Create internal representation of primary key based on given values.
     *
     * @param  Zend_Db_Table_Row_Abstract|array $id
     * @return array
     */
    protected function _normalizeId($id)
    {
        $primary = $this->info(self::PRIMARY);

        if ($id instanceof Zend_Db_Table_Row_Abstract) {
            $id = $id->toArray();

        } elseif (!is_array($id)) {
            // scalar value given, assume one-column primary key
            foreach ($primary as $column) {
                $id = array($column => $id);
                break;
            }
        }

        $normalized = array();

        foreach ($primary as $column) {
            if (isset($id[$column])) {
                $normalized[$column] = strval($id[$column]);
            } else {
                throw new Exception('Incomplete primary key values');
            }
        }

        return $normalized;
    }

    /**
     * Replacement for {@see Zend_Db_Table_Abstract::delete()} that uses
     * for Table instance retrieval {@see getTable()} method instead of
     * {@see Zend_Db_Table_Abstract::getTableFromString()}.
     *
     * @param  array|string $where
     * @return int
     */
    public function delete($where)
    {
        $depTables = $this->getDependentTables();

        if ($depTables) {
            foreach ($this->fetchAll($where) as $row) {
                foreach ($depTables as $tableClass) {
                    $table = $this->getTable($tableClass);
                    $table->_cascadeDelete($tableClass, $row->getPrimaryKey());
                }
            }
        }

        return $this->_db->delete($this->getQualifiedName(), $where);
    }

    /**
     * Get table instance by class name. This method is essentially a proxy
     * to {@link Zefram_Db::getTable()} called with this object's database
     * adapter.
     *
     * @param  string $tableClass
     * @return Zend_Db_Table_Abstract
     */
    public function getTable($tableClass = null)
    {
        if (null === $tableClass) {
            return $this;
        }

        if ($tableClass instanceof Zend_Db_Table_Abstract) {
            return $tableClass;
        }

        if ($this->hasTableProvider()) {
            return $this->getTableProvider()->getTable($tableClass, $this->getAdapter());
        }

        return Zefram_Db::getTable($tableClass, $this->getAdapter());
    }

    /**
     * @param Zefram_Db_Table_Provider $tableProvider
     * @return Zefram_Db_Table
     */
    public function setTableProvider(Zefram_Db_Table_Provider $tableProvider)
    {
        $this->_tableProvider = $tableProvider;
        return $this->_tableProvider;
    }

    /**
     * @return Zefram_Db_Table_Provider
     */
    public function getTableProvider()
    {
        return $this->_tableProvider;
    }

    /**
     * @return bool
     */
    public function hasTableProvider()
    {
        return null !== $this->_tableProvider;
    }
}
