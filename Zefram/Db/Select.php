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
        $db = $this->getAdapter();
        if (is_array($cond)) {
            foreach ($cond as $key => $value) {
                if (is_int($key)) {
                    $value = Zefram_Db::quoteEmbeddedIdentifiers($db, $value);
                    parent::where($value);
                } else {
                    $key = Zefram_Db::quoteEmbeddedIdentifiers($db, $key);
                    parent::where($key, $value);
                }
            }
        } else {
            $cond = Zefram_Db::quoteEmbeddedIdentifiers($db, $cond);
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
        $db = $this->getAdapter();
        if (is_array($cond)) {
            foreach ($cond as $key => $value) {
                if (is_int($key)) {
                    $value = Zefram_Db::quoteEmbeddedIdentifiers($db, $value);
                    parent::orWhere($value);
                } else {
                    $key = Zefram_Db::quoteEmbeddedIdentifiers($db, $key);
                    parent::orWhere($key, $value);
                }
            }
        } else {
            $cond = Zefram_Db::quoteEmbeddedIdentifiers($db, $cond);
            parent::orWhere($cond, $value, $type); 
        }
        return $this;
    }

    /**
     * @param  string $cond
     * @param  array $params
     * @return Zefram_Db_Select
     */
    public function whereParams($cond, array $params)
    {
        $db = $this->getAdapter();

        $cond = Zefram_Db::quoteEmbeddedIdentifiers($db, $cond);
        $cond = Zefram_Db::quoteParamsInto($db, $cond, $params);

        return parent::where($cond);
    }

    /**
     * @param  string $cond
     * @param  array $params
     * @return Zefram_Db_Select
     */
    public function orWhereParams($cond, array $params)
    {
        $db = $this->getAdapter();

        $cond = Zefram_Db::quoteEmbeddedIdentifiers($db, $cond);
        $cond = Zefram_Db::quoteParamsInto($db, $cond, $params);

        return parent::orWhere($cond);
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
        $table = null;

        // Allow Zend_Db_Table_Abstract instances to be passed as table names

        // replace any Zend_Db_Table_Abstract instance with its name and schema
        if ($name instanceof Zend_Db_Table_Abstract) {
            $name = array($name);
        }
        if (is_array($name)) {
            foreach ($name as $correlationName => $tableName) {
                if ($tableName instanceof Zend_Db_Table_Abstract) {
                    $table = $tableName;
                    $name[$correlationName] = $tableName->info(Zend_Db_Table_Abstract::NAME);
                    $schema = $tableName->info(Zend_Db_Table_Abstract::SCHEMA);
                }
                break;
            }
        }

        if (!is_array($cols)) {
            // don't cast to array as there may be Zend_Db_Expr instances
            $cols = array($cols);
        }

        // DEPRECATED
        // remove columns which are marked as false, replace true values
        // with column names
        if (is_array($cols)) {
            foreach ($cols as $key => $value) {
                if (is_bool($value)) {
                    try {
                        throw new Exception;
                    } catch (Exception $e) {
                        $trace = $e->getTrace();
                        $last = reset($trace);
                        trigger_error(sprintf(
                            'Using boolean column spec is deprecated in %s(). Called in %s on line %d',
                            __METHOD__, $last['file'], $last['line']
                        ), E_USER_NOTICE);
                    }
                }
                if (false === $value) {
                    unset($cols[$key]);
                } elseif (true === $value) {
                    $cols[$key] = $key;
                }
            }
        }

        // Join conditions given as array, automaticall quote embedded identifiers

        // Overcome another deficiency of Zend_Db_Select - join conditions can
        // only be given as a single string. Quoting of identifiers and values
        // must be done manually.

        // Here join conditions can be given just like to WHERE clause:
        // - join conditions can be given as array, values will be quoted into
        //   string keys
        // - table identifiers are automatically quoted
        if (is_array($cond)) {
            foreach ($cond as $key => $value) {
                if (is_string($key)) {
                    unset($cond[$key]);

                    $cond[] = '(' . $db->quoteInto(
                        Zefram_Db::quoteEmbeddedIdentifiers($db, $key),
                        $value
                    ) . ')';

                } else {
                    $cond[$key] = '(' . Zefram_Db::quoteEmbeddedIdentifiers($db, $value) . ')';
                }
            }
            $cond = implode(' AND ', $cond);

        } elseif (is_string($cond)) {
            // quote identifiers present in JOIN condition, identifiers are
            // expected to be in the form table.column
            $cond = Zefram_Db::quoteEmbeddedIdentifiers($db, $cond);
        }

        return compact('name', 'cond', 'cols', 'schema');
    }

    /**
     * Prepare mapping between prefixed and actual column names of a given
     * table.
     *
     * Output from this function is suitable for use when building Select objects.
     *
     * @param  Zend_Db_Table_Abstract $table
     *     table to get column information from
     * @param  string $prefix OPTIONAL
     *     column name prefix
     * @param  string|array $exclude OPTIONAL
     *     names of columns to be excluded from output
     * @return array
     *
     * @deprecated
     */
    public static function tableCols(Zend_Db_Table_Abstract $table, $prefix = null, $exclude = null)
    {
        if ($table instanceof Zefram_Db_Table) {
            $cols = $table->getCols();
        } else {
            $cols = $table->info(Zend_Db_Table_Abstract::COLS);
        }

        // remove unwanted columns
        if ($exclude) {
            $exclude = array_flip(array_map('strtolower', (array) $exclude));

            foreach ($cols as $position => $col) {
                if (isset($exclude[strtolower($col)])) {
                    unset($cols[$position]);
                }
            }
        }

        $columnMap = array();

        foreach ($cols as $col) {
            $columnMap[$prefix . $col] = $col;
        }

        return $columnMap;
    }
}
