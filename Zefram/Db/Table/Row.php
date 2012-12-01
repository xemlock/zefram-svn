<?php

class Zefram_Db_Table_Row extends Zend_Db_Table_Row
{
    protected $_tableClass = 'Zefram_Db_Table';
    protected $_referencedRows = array();

    public function getAdapter()
    {
        $table = $this->getTable();

        if (empty($table)) {
            throw new Zend_Db_Table_Row_Exception('Row is not connected to a table');
        }

        return $table->getAdapter();
    }

    /**
     * Seamlessly fetch parent row by reference rule using {@see Zefram_Db_Table::findRow()}
     * mechanism. Rule name must start with an uppercase letter.
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

                    throw new Zefram_Db_Table_Row_ReferentialIntegrityException(sprintf(
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
}
