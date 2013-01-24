<?php

abstract class Zefram_Db_Traits
{
    public static function bindParams(Zend_Db_Adapter_Abstract $db, $sql, $bind)
    {
        foreach ((array) $bind as $key => $value) {
            $value = $db->quote($value);

            if (is_int($key)) {
                // replace first question mark with this parameter
                $sql = preg_replace('/\?/', $value, $sql, 1);

            } else if (preg_match('/^:[a-z][_A-Z0-9]+$/i', $key)) {
                // replace named parameters placeholders with given value
                $sql = str_replace($key, $value, $sql);

            } else {
                throw new Zend_Db_Exception("Invalid parameter name '$key'");
            }
        }
    }
}
