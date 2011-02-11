<?php

abstract class Zefram_Db_Driver 
{
    private static $_driver = 'Zefram_Db_Driver_Zend';

    public static function set($driver) 
    {
        require_once 'Zefram/Db/Driver/Abstract.php';
        if (!is_subclass_of($driver, 'Zefram_Db_Driver_Abstract')) {
            throw new Exception('Supplied driver is not a subclass of Zefram_Db_Driver');
        }
        self::$_driver = $driver;
    }

    public static function get($modelName) 
    {
        return new self::$_driver($modelName);
    }

    public static function getDefaultConnection()
    {
        $driver = self::$_driver;
        return $driver::getDefaultConnection();
    }

    public static function translateException(Exception $e)
    {
        $driver = self::$_driver;
        return $driver::translateException($e);
    }

    public static function tableName($modelName) 
    {
        return strtolower(
            substr($modelName, 0, 1) . 
            preg_replace('/([A-Z])/', '_$1', substr($modelName, 1))
        );
    }
}

