<?php

class Zefram_Db_Table extends Zend_Db_Table
{
    protected $_rowClass = 'Zefram_Db_Table_Row';

    /**
     * Storage for rows fetched by {@link findRow()}.
     * @var array
     */
    protected $_identityMap = array();

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


    // does anybody know why these are missing in Zend_Db
    // info() is somewhat inconvenient
    public function getName()
    {
        return $this->_name;
    }

    public function getSchema()
    {
        return $this->_schema;
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
     * Count rows matching $where
     *
     * @param string|array $where
     * @return int
     */
    public function countAll($where = null)
    {
        $select = $this->select();
        $select->from($this->_name, 'COUNT(1) AS cnt');

        if (null !== $where) {
            $this->_where($select, $where);
        }

        $row = $this->fetchRowAsArray($select);
        return intval($row['cnt']);
    }

    /**
     * Finds a record by its identifier.
     *
     * @param mixed $id
     *     Database row ID
     * @return mixed
     *     Zend_Db_Table_Row or false if no result
     * @throws Exception
     *     when incomplete primary key values given or if column does not
     *     belong to primary key
     */
    public function findRow($id)
    {
        $id = $this->_normalizeId($id);
        $key = serialize($id);

        if (!array_key_exists($key, $this->_identityMap)) {
            $primary = $this->info(self::PRIMARY);
            $db = $this->getAdapter();
            $where = array();

            foreach ($primary as $column) {
                if (isset($id[$column])) {
                    $where[] = $db->quoteIdentifier($column) . ' = ' . $db->quote($id[$column]);
                }
            }

            if (count($where) != count($primary)) {
                throw new Exception('Incomplete primary key values');
            }

            $where = implode(' AND ', $where);
            $this->_identityMap[$key] = $this->fetchRow($where);
        }

        return $this->_identityMap[$key];
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
     * @param Zend_Db_Table_Row_Abstract|array $id
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
                $normalized[$column] = (string) $id[$column];
            }
        }

        return $normalized;
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

            $select->from($name, $columns, $this->info(self::SCHEMA));            
        }

        return $select;
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

        return Zefram_Db::getTable($tableClass, $this->getAdapter());
    }

    /**
     * Begin transaction on an underlying database adapter.
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function beginTransaction()
    {
        return $this->getAdapter()->beginTransaction();
    }

    /**
     * Commit a transaction.
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function commit()
    {
        return $this->getAdapter()->commit();
    }

    /**
     * Roll back a transaction.
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function rollBack()
    {
        return $this->getAdapter()->rollBack();
    }
}
