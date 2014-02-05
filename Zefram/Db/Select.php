<?php

class Zefram_Db_Select extends Zend_Db_Select
{
    /**
     * Enhancement for {@see Zend_Db_Select::_join()} allowing Zend_Db_Table
     * instances to be passed as the $name parameter.
     *
     * @param  null|string $type
     *     Type of join
     * @param  string|array|Zend_Db_Expr|Zend_Db_Table_Abstract $name
     *     Table name or instance
     * @param  string|array $cond
     *     Join on this condition
     * @param  string|array $cols
     *     The columns to select from the joined table
     * @param  string $schema
     *     The database name to specify, if any.
     * @return Zefram_Db_Select
     *     This object
     * @throws Zend_Db_Select_Exception
     */
    protected function _join($type, $name, $cond, $cols, $schema = null)
    {
        extract(self::_prepareJoin(
            $this->getAdapter(), $name, $cond, $cols, $schema
        ));
        return parent::_join($type, $name, $cond, $cols, $schema);
    }

    /**
     * Adds a WHERE condition to the query by AND.
     *
     * @param string|array $cond
     *     The WHERE condition.
     * @param mixed $value OPTIONAL
     *     The value to quote into the condition, used when condition
     *     is not an array
     * @param int $type OPTIONAL
     *     The type of the given value, used when condition is not an array
     * @return Zefram_Db_Select
     *     This select object
     */
    public function where($cond, $value = null, $type = null)
    {
        if (is_array($cond)) {
            foreach ($cond as $key => $value) {
                if (is_int($key)) {
                    parent::where($value);
                } else {
                    parent::where($key, $value);
                }
            }
        } else {
            parent::where($cond, $value, $type); 
        }
        return $this;
    }

    /**
     * Adds a WHERE condition to the query by OR.
     *
     * @param string|array $cond
     *     The WHERE condition.
     * @param mixed $value OPTIONAL
     *     The value to quote into the condition, used when condition
     *     is not an array
     * @param int $type OPTIONAL
     *     The type of the given value, used when condition is not an array
     * @return Zefram_Db_Select
     *     This select object
     */
    public function orWhere($cond, $value = null, $type = null)
    {
        if (is_array($cond)) {
            foreach ($cond as $key => $value) {
                if (is_int($key)) {
                    parent::orWhere($value);
                } else {
                    parent::orWhere($key, $value);
                }
            }
        } else {
            parent::orWhere($cond, $value, $type); 
        }
        return $this;
    }

    /**
     * Creates a new Select object for given adapter.
     *
     * @param  Zend_Db_Adapter_Abstract $adapter
     * @return Zefram_Db_Select
     */
    public static function factory(Zend_Db_Adapter_Abstract $adapter)
    {
        return new self($adapter);
    }

    /**
     * @param  Zend_Db_Adapter_Abstract $db
     * @param  null|string $type
     *     Type of join
     * @param  array|string|Zend_Db_Expr|Zend_Db_Table $name
     *     Table instance or name
     * @param  string $cond
     *     Join on this condition
     * @param array|string $cols
     *     The columns to select from the joined table
     * @param  string $schema
     *     OPTIONAL The database name to specify, if any. If $name parameter
     *     is an instance of Zend_Db_Table, schema of this instance is used
     * @return array
     *     Array containing name, cond, cols and schema variables understandable
     *     by {@see Zend_Db_Select::_join()} method.
     * @internal
     */
    public static function _prepareJoin(Zend_Db_Adapter_Abstract $db, $name, $cond, $cols, $schema)
    {
        // replace all instances of Zend_Db_Table_Abstract with their
        // names and schemas
        if ($name instanceof Zend_Db_Table_Abstract) {
            $name = array($name);
        }
        if (is_array($name)) {
            foreach ($name as $correlationName => $tableName) {
                if ($tableName instanceof Zend_Db_Table_Abstract) {
                    $name[$correlationName] = $tableName->info(Zend_Db_Table_Abstract::NAME);
                    $schema = $tableName->info(Zend_Db_Table_Abstract::SCHEMA);
                }
                break;
            }
        }

        // remove columns which are marked as false, replace true values
        // with column names
        if (is_array($cols)) {
            foreach ($cols as $key => $value) {
                if (false === $value) {
                    unset($cols[$key]);
                } elseif (true === $value) {
                    $cols[$key] = $key;
                }
            }
        }

        if (is_array($cond)) {
            $cond = implode(' AND ', $cond);
        }
        // quote identifiers present in JOIN condition, identifiers are
        // expected to be in the form table.column
        if (is_string($cond)) {
            $cond = preg_replace_callback(
                '/(?P<table>[_a-z][_a-z0-9]*)\.(?P<column>[_a-z][_a-z0-9]*)/i',
                self::_quoteJoinIdentifier($db), $cond
            );
        }

        return compact('name', 'cond', 'cols', 'schema');
    }

    /**
     * @param array|Zend_Db_Adapter_Abstract $dbOrMatch
     * @return string|callback
     * @internal
     */
    protected function _quoteJoinIdentifier($dbOrMatch)
    {
        static $db;

        if ($dbOrMatch instanceof Zend_Db_Adapter_Abstract) {
            $db = $dbOrMatch;
            return array(__CLASS__, __FUNCTION__);
        }

        return $db->quoteIdentifier($dbOrMatch['table']) . '.' . $db->quoteIdentifier($dbOrMatch['column']);
    }
}
