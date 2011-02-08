<?php

abstract class ZUtils_Db_Driver 
{
    private static $_driver = 'ZUtils_Db_Driver_Zend';

    public static function set($driver) 
    {
        require_once 'ZUtils/Db/Driver/Abstract.php';
        if (!is_subclass_of($driver, 'ZUtils_Db_Driver_Abstract')) {
            throw new Exception('Supplied driver is not a subclass of ZUtils_Db_Driver');
        }
        self::$_driver = $driver;
    }

    public static function get($modelName) 
    {
        return new self::$_driver($modelName);
    }

    public static function getDefaultConnection()
    {
        return get_class(self::$_driver)::getDefaultConnection();
    }

    public static function tableName($modelName) 
    {
        return strtolower(
            substr($modelName, 0, 1) . 
            preg_replace('/([A-Z])/', '_$1', substr($modelName, 1))
        );
    }
}

