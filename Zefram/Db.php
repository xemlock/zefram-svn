<?php

/**
 * Contrary to class name which suggests similarity to Zend_Db
 * this class' reason for existence is being a registry for table objects.
 */
abstract class Zefram_Db
{
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

    protected static $_tablePrefix;
    protected static $_tableRegistry;

    public static function setTablePrefix($prefix)
    {
        if (!preg_match('/^[_A-Za-z][_0-9A-Za-z]*$/', $prefix)) {
            throw new Exception('Invalid class prefix provided');
        }
        self::$_tablePrefix = $prefix;
    }

    public static function getTablePrefix($prefix)
    {
        return self::$_tablePrefix;
    }

    public static function getTable($className, Zend_Db_Adapter_Abstract $db = null, $addPrefix = true)
    {
        if (null === $db) {
            $db = Zefram_Db_Table::getDefaultAdapter();
        }
        if (null === $db) {
            throw new Exception('No default database adapter found');
        }

        if ($addPrefix && (0 !== strpos($className, self::$_tablePrefix))) {
            // add prefix only if it's not already included
            $fullClassName = self::$_tablePrefix . $className;

        } else {
            $fullClassName = $className;
        }

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
