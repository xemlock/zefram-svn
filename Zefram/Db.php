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
