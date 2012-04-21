<?php

class Zefram_Db_Table extends Zend_Db_Table_Abstract
{
    /**
     * Fetches all rows, but returns them as arrays instead of objects.
     * See {@link Zend_Db_Table_Abstract::fetchAll()} for parameter explanation.
     *
     * @return array
     */
    public function fetchAllAsArray($where = null, $order = null, $count = null, $offset = null)
    {
        if (!($where instanceof Zend_Db_Table_Select)) {
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
                $this->_where($select, $this->_whereId($where));
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
    // info() is extremely inconvenient
    public function getName($quote = false)
    {
        return $quote
             ? $this->getAdapter()->quoteIdentifier($this->_name)
             : $this->_name;
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
            $this->_where($select, $this->_whereId($where));
        }

        $row = $this->fetchRowAsArray($select);
        return intval($row['cnt']);
    }

    // numeric $where value is treated as pk = value condition
    // (works just like find but returns single row on success
    // rather than rowset).
    public function fetchRow($where = null, $order = null)
    {
        if (is_scalar($where)) {
            trigger_error(__METHOD__ . ': obsolete call detected. Use fetchById() instead', E_USER_NOTICE);
        }
        return parent::fetchRow($this->_whereId($where), $order);
    }

    /**
     * Storage for rows fetched by {@link find()}.
     * @var array
     */
    protected $_identityMap;

    /**
     * Finds a record by its identifier.
     *
     * @param mixed $id           Database row ID
     * @return mixed              Zend_Db_Table_Row or false if no result
     */
    public function find($id)
    {
        $id  = array_values((array) $id);
        $key = implode(' ', $id);

        if (!isset($this->_identityMap[$key])) {
            $primary = $this->info(Zend_Db_Table_Abstract::PRIMARY);

            if (count($id) != count($primary)) {
                throw new Exception('Incomplete primary key');
            }

            $db    = $this->getAdapter();
            $where = array();

            foreach (array_values($primary) as $position => $name) {
                $where[$db->quoteIdentifier($name) . ' = ?'] = $id[$position];
            }

            $this->_identityMap[$key] = $this->fetchRow($where);
        }

        return $this->_identityMap[$key];
    }


    /**
     * If parameter is scalar and integer it is converted to
     * primary key condition.
     */
    protected function _whereId($where)
    {
        if (is_scalar($where) && ctype_digit((string) $where)) {
            // numeric primary key
            $primary = $this->info(Zend_Db_Table_Abstract::PRIMARY);
            if (count($primary) == 1) {
                // only scalar primary key
                $primary = reset($primary);
                $where = array($primary . ' = ?' => intval($where));
            }
        }
        return $where;
    }
}
