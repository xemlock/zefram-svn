<?php

/**
 * Contraty to class name which suggests similarity to Zend_Db
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

    public static function getTable($tableName)
    {
        $tableName = self::$_tablePrefix . $tableName;
        if (!isset(self::$_tableRegistry[$tableName])) {
            self::$_tableRegistry[$tableName] = new $tableName;
        }
        return self::$_tableRegistry[$tableName];
    }
}
