<?php

/**
 * Class for building DbTable instances.
 *
 * @category   Zefram
 * @package    Zefram_Db
 * @subpackage Table
 */
class Zefram_Db_Table_Provider
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;

    /**
     * @var Zend_Db_Table_Definition
     */
    protected $_tableDefinition;

    /**
     * @var string
     */
    protected $_tablePrefix;

    /**
     * @var string
     */
    protected $_tableClass = 'Zefram_Db_Table';

    /**
     * @param  Zend_Db_Adapter_Abstract $db
     * @param  array $options
     * @return void
     */
    public function __construct(Zend_Db_Adapter_Abstract $db, $options = null)
    {
        $this->setAdapter($db);

        if ($options) {
            $this->setOptions($options);
        }
    }

    /**
     * @param  array $options
     * @return Zefram_Db_Table_Provider
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . $key;
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
     * @param  Zend_Db_Adapter_Abstract $db
     * @return Zefram_Db_Table_Provider
     */
    public function setAdapter(Zend_Db_Adapter_Abstract $db)
    {
        $this->_db = $db;
        return $this;
    }

    /**
     * @return Zend_Db_Adapter_Abstract
     */
    public function getAdapter()
    {
        return $this->_db;
    }

    /**
     * @param  Zend_Db_Table_Definition $tableDefinition
     * @return Zefram_Db_Table_Provider
     */
    public function setTableDefinition(Zend_Db_Table_Definition $tableDefinition)
    {
        $this->_tableDefinition = $tableDefinition;
        return $this;
    }

    /**
     * @return Zend_Db_Table_Definition|null
     */
    public function getTableDefinition()
    {
        return $this->_tableDefinition;
    }

    /**
     * @param  string $prefix
     * @return Zefram_Db_Table_Provider
     */
    public function setTablePrefix($tablePrefix)
    {
        $this->_tablePrefix = strval($tablePrefix);
        return $this;
    }

    /** 
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->_tablePrefix;
    }

    /**
     * @param  string $tableName
     * @param  Zend_Db_Adapter_Abstract|string $db OPTIONAL
     * @return Zend_Db_Table_Abstract
     */
    public function getTable($tableName, $db = null)
    {
        $tableDefinition = $this->getTableDefinition();

        if ($tableDefinition && $tableDefinition->hasTableConfig($tableName)) {
            $table = new $this->_tableClass($tableName, $tableDefinition);

        } else {
            // assume tableName is a class name
            if (!class_exists($tableName)) {
                try {
                    Zend_Loader::loadClass($tableName);
                } catch (Zend_Exception $e) {
                    throw new Zend_Db_Table_Exception($e->getMessage(), $e->getCode(), $e);
                }
            }

            $options = array();

            if (empty($db)) {
                $options[Zend_Db_Table_Abstract::ADAPTER] = $this->getAdapter();
            } else {
                $options[Zend_Db_Table_Abstract::ADAPTER] = $db;
            }

            if ($tableDefinition) {
                $options[Zend_Db_Table_Abstract::DEFINITION] = $tableDefinition;
            }

            $table = new $tableName($options);
        }

        if (!$table instanceof Zend_Db_Table_Abstract) {
            throw new Zend_Db_Table_Exception(sprintf(
                'Table is expected to be a instance of Zend_Db_Table_Abstract, got %s instead',
                get_class($table)
            ));
        }

        // pass table provider to the table instance
        if ($table instanceof Zefram_Db_Table) {
            $table->setTableProvider($this);
        }

        if ($this->_tablePrefix) {
            if ($table instanceof Zefram_Db_Table) {
                $name = $table->getName();
            } else {
                // Zend_Db_Table_Abstract way of retrieving table name via
                // info() sucks, as there is no non-hackish way of retrieving
                // table name without fetching table's metadata from db.
                // Since PHP's reflection does not allow to access protected
                // properties we need to retrieve by taking advantage of how
                // PHP engine casts objects to arrays.
                //
                // When object is cast to an array, private properties
                // are stored at "\x00ClassName\x00propertyName" key, protected
                // properties at "\x00*\x00propertyName".
                //
                // See more:
                //   http://derickrethans.nl/private-properties-exposed.html
                //   http://stackoverflow.com/questions/6325447/array-to-object-and-object-to-array-in-php-interesting-behaviour
                $tableArray = (array) $table;
                $name = $tableArray["\x00*\x00_name"];
            }

            $table->setOptions(array(
                Zend_Db_Table_Abstract::NAME => $this->_tablePrefix . $name,
            ));
        }

        return $table;
    }
}
