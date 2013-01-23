<?php

class Zefram_Db_Adapter_Pdo_Mysql extends Zend_Db_Adapter_Pdo_Mysql
{
    public function getTable($name)
    {
        return Zefram_Db::getTable($name, $this);
    }

    /**
     * Removes all special characters from string to be used as a pattern
     * in LIKE expression (not REGEXP / RLIKE in MySQL or SIMILAR TO in
     * PostgreSQL). This function does not quote given string.
     *
     * @param string $pattern
     * @param string $escapeChar
     * @return string
     */
    public function escapePattern($string, $escapeChar = '\\')
    {
        $forbidden = array('%', '_');
        $escaped = array($escapeChar . '%', $escapeChar . '_');

        return str_replace($forbidden, $escaped, $string);
    }
}
