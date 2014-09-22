<?php

class Zefram_Db implements Zefram_Db_TransactionManager
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_adapter;

    /**
     * @var Zefram_Db_Table_Factory
     */
    protected $_tableFactory;

    /**
     * @var int
     */
    protected $_transactionLevel = 0;

    /**
     * Factory for Db objects.
     *
     * @param  string|Zend_Config $adapter
     * @param  array|Zend_Config $config OPTIONAL
     * @return Zefram_Db
     */
    public static function factory($adapter, $config = array()) // {{{
    {
        $adapter = Zend_Db::factory($adapter, $config);
        return new self($adapter);
    } // }}}

    /**
     * Constructor.
     *
     * @param Zend_Db_Adapter_Abstract|Zefram_Db_Table_FactoryInterface $adapter
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct($adapter) // {{{
    {
        if ($adapter instanceof Zefram_Db_Table_FactoryInterface) {
            $this->_adapter = $adapter->getAdapter();
            $this->_tableFactory = $adapter;
        } elseif ($adapter instanceof Zend_Db_Adapter_Abstract) {
            $this->_adapter = $adapter;
        } else {
            throw new InvalidArgumentException('Adapter must be either an instance of Zend_Db_Adapter_Abstract or Zefram_Db_Table_FactoryInterface');
        }
    } // }}}

    /**
     * @return Zend_Db_Adapter_Abstract
     */
    public function getAdapter() // {{{
    {
        return $this->_adapter;
    } // }}}

    /**
     * @return Zefram_Db_Table_FactoryInterface
     */
    public function getTableFactory() // {{{
    {
        if ($this->_tableFactory === null) {
            $this->_tableFactory = new Zefram_Db_Table_Factory($this->_adapter);
        }
        return $this->_tableFactory;
    } // }}}

    /**
     * @return Zefram_Db
     */
    public function beginTransaction() // {{{
    {
        if ($this->_transactionLevel === 0) {
            // increase level counter _after_ beginning transaction,
            // in case an exception is thrown
            $this->_adapter->beginTransaction();
            ++$this->_transactionLevel;
        }
        return $this;
    } // }}}

    /**
     * @return Zefram_Db
     */
    public function rollBack() // {{{
    {
        if ($this->_transactionLevel === 1) {
            $this->_adapter->rollBack();
        }
        --$this->_transactionLevel;
        return $this;
    } // }}}

    /**
     * @return Zefram_Db
     */
    public function commit() // {{{
    {
        if ($this->_transactionLevel === 1) {
            $this->_adapter->commit();
        }
        --$this->_transactionLevel;
        return $this;
    } // }}}

    /**
     * @return bool
     */
    public function inTransaction() // {{{
    {
        return ($this->_transactionLevel > 0);
    } // }}}

    /**
     * @return Zend_Db_Table_Abstract
     */
    public function getTable2($name) // {{{
    {
        return $this->getTableFactory()->getTable($name);
    } // }}}

    /**
     * @param  string $prefix
     * @return Zefram_Db
     */
    public function setTablePrefix($prefix) // {{{
    {
        $this->getTableFactory()->setTablePrefix($prefix);
        return $this;
    } // }}}

    /**
     * @return string
     */
    public function getTablePrefix() // {{{
    {
        return $this->getTableFactory()->getTablePrefix();
    } // }}}


    protected static $_tableRegistry;

    /**
     * Quote parameters into given string using named parameters notation,
     * regardless of whether database adapter supports named parameters or not.
     *
     * Named parameters 
     * @param  Zend_Db_Adapter_Abstract $db
     * @param  string $string
     * @param  array $params
     * @return string
     * @throws InvalidArgumentException
     */
    public static function quoteParamsInto(Zend_Db_Adapter_Abstract $db, $string, array $params) // {{{
    {
        $replace = array();
        $position = 0;

        // build replacement pairs for positional and named parameters
        foreach ($params as $name => $value) {
            $quoted = $db->quote($value);

            if ($name === $position) {
                $replace['?' . ($position + 1)] = $quoted;

            } elseif (preg_match('/^[_A-Z][_0-9A-Z]*$/i', $name)) {
                $replace[':' . $name] = $quoted;
                $replace['?' . ($position + 1)] = $quoted;

            } else {
                throw new InvalidArgumentException(sprintf(
                    'Invalid parameter name: %s', $name
                ));
            }

            ++$position;
        }

        // use strtr() and not str_replace() to avoid recursive replacements
        return strtr($string, $replace);
    } // }}}

    /**
     * @param  Zend_Db_Adapter_Abstract $db
     * @param  string $string
     * @return string
     */
    public static function quoteEmbeddedIdentifiers(Zend_Db_Adapter_Abstract $db, $string) // {{{
    {
        return preg_replace_callback(
            '/(?P<table>[_a-z][_a-z0-9]*)\.(?P<column>[_a-z][_a-z0-9]*)/i',
            self::_quoteEmbeddedIdentifiersCallback($db), $string
        );
    } // }}}

    /**
     * @param Zend_Db_Adapter_Abstract|array $dbOrMatch
     * @return string|callback
     * @internal
     */
    protected static function _quoteEmbeddedIdentifiersCallback($dbOrMatch) // {{{
    {
        static $db;

        if ($dbOrMatch instanceof Zend_Db_Adapter_Abstract) {
            $db = $dbOrMatch;
            return array(__CLASS__, __FUNCTION__);
        }

        return $db->quoteIdentifier($dbOrMatch['table']) . '.' . $db->quoteIdentifier($dbOrMatch['column']);
    } // }}}

    public static function getTable($className, Zend_Db_Adapter_Abstract $db = null, $addPrefix = true)
    {
        if (null === $db) {
            $db = Zefram_Db_Table::getDefaultAdapter();
        }
        if (null === $db) {
            throw new Exception('No default database adapter found');
        }

        $fullClassName = $className;

        $adapterId = spl_object_hash($db);

        if (!isset(self::$_tableRegistry[$adapterId][$fullClassName])) {
            if (class_exists($fullClassName, true)) {
                // ok, class found
                $dbTable = new $fullClassName(array(
                    'db' => $db,
                ));
            } else {
                // no class found, simulate it with basic Db_Table with only
                // table name set
                $tableName = self::classToTable($className);
                $dbTable = new Zefram_Db_Table(array(
                    'db' => $db,
                    'name' => $tableName,
                ));
            }
            self::$_tableRegistry[$adapterId][$fullClassName] = $dbTable;
        }
        return self::$_tableRegistry[$adapterId][$fullClassName];
    }

    // convert camel-case to underscore separated
    public static function classToTable($className)
    {
        return strtolower(
            substr($className, 0, 1) . 
            preg_replace('/([A-Z])/', '_$1', substr($className, 1))
        );    
    }
}
