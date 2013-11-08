<?php

class Zefram_Db_Select extends Zend_Db_Select
{
    /**
     * @param  null|string $type
     *     Type of join
     * @param  array|string|Zend_Db_Expr|Zend_Db_Table_Abstract $name
     *     Table name or instance
     * @param  string $cond
     *     Join on this condition
     * @param  array|string $cols
     *     The columns to select from the joined table
     * @param  string $schema
     *     The database name to specify, if any.
     * @return Zefram_Db_Select
     *     This object
     * @throws Zend_Db_Select_Exception
     */
    protected function _join($type, $name, $cond, $cols, $schema = null)
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
        // quote identifiers present in JOIN condition, identifiers are
        // expected to be in the form table.column
        if (is_string($cond)) {
            $cond = preg_replace_callback(
                '/(?P<table>[_a-z][_a-z0-9]*)\.(?P<column>[_a-z][_a-z0-9]*)/i',
                array($this, '_quoteJoinIdentifier'),
                $cond
            );
        }
        return parent::_join($type, $name, $cond, $cols, $schema);
    }

    /**
     * @param array $match
     * @return string
     * @internal
     */
    public function _quoteJoinIdentifier(array $match)
    {
        $db = $this->getAdapter();
        return $db->quoteIdentifier($match['table']) . '.' . $db->quoteIdentifier($match['column']);
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
}
