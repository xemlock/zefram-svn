<?php

/**
 * Contrary to class name which suggests similarity to Zend_Db
 * this class' reason for existence is being a registry for table objects.
 */
abstract class Zefram_Db
{
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

    public static function getTable($className)
    {
        $fullClassName = self::$_tablePrefix . $className;
        if (!isset(self::$_tableRegistry[$fullClassName])) {
            if (class_exists($fullClassName, true)) {
                // ok, class found
                self::$_tableRegistry[$fullClassName] = new $fullClassName;
            } else {
                // no class found, simulate it with basic Db_Table with only
                // table name set
                $tableName = self::classToTable($className);
                $dbTable = new Zefram_Db_Table(array('name' => $tableName));
                self::$_tableRegistry[$fullClassName] = $dbTable;
            }
        }
        return self::$_tableRegistry[$fullClassName];
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
