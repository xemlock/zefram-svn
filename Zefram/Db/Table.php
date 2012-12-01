<?php

class Zefram_Db_Table extends Zend_Db_Table_Abstract
{
    protected $_rowClass = 'Zefram_Db_Table_Row';

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
        return $this->info(self::NAME);
    }

    public function getQuotedName()
    {
        return $this->getAdapter()->quoteIdentifier($this->info(self::NAME));
    }

    public function getSchema()
    {
        return $this->_schema;
    }

    /**
     * Count rows matching $where
     *
     * @return int
     */
    public function countAll($where = null)
    {
        $select = $this->select();
        $select->from($this->_name, 'COUNT(*) AS cnt');

        if (null !== $where) {
            $this->_where($select, $where);
        }

        $row = $this->fetchRowAsArray($select);
        return intval($row['cnt']);
    }

    /**
     * Storage for rows fetched by {@link findRow()}.
     * @var array
     */
    protected $_identityMap;

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
        $primary = array_values($this->info(Zend_Db_Table_Abstract::PRIMARY));

        if (!is_array($id)) {
            $id = array($primary[0] => (string) $id);
        } else {
            $id = array_map('strval', $id);
        }

        ksort($id);
        $key = serialize($id);

        if (!isset($this->_identityMap[$key])) {
            $db    = $this->getAdapter();
            $where = array();

            foreach ($primary as $column) {
                if (isset($id[$column])) {
                    $where[] = $db->quoteIdentifier($column) . ' = ' . $db->quote($id[$column]);
                }
            }

            if (count($where) != count($primary)) {
                throw new Exception('Incomplete primary key values');
            }

            $this->_identityMap[$key] = $this->fetchRow($where);
        }

        return $this->_identityMap[$key];
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
     * @return Zefram_Db_Select
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
}
