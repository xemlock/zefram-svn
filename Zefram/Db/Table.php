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
     * @param mixed $id         Database row ID
     * @return mixed            Zend_Db_Table_Row or false if no result
     * @throws Exception        incomplete primary key values given
     *                          or if column does not belong to primary key
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
                    $where[$db->quoteIdentifier($column) . ' = ?'] = $id[$column];
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

    /*
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
     */
}
