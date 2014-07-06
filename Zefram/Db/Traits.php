<?php

abstract class Zefram_Db_Traits
{
    public static function bindParams(Zend_Db_Adapter_Abstract $db, $sql, $bind)
    {
        foreach ((array) $bind as $key => $value) {
            // nulls can be passed directly, no need for new Zend_Db_Expr('NULL')
            if (null === $value) {
                $value = 'NULL';
            } else {
                $value = $db->quote($value);
            }

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

        return $sql;
    }

    /**
     * Normalize value.
     *
     * PDO by default treats all values as strings. This however, is not
     * necessarily true for other code utilizing database access layer.
     * This method aims to mitigate effects of such type inconsistencies.
     *
     * @param  mixed
     * @return mixed
     */
    public function normalizeValue($value)
    {
        if (is_int($value) || is_bool($value) || is_null($value)) {
            return $value;
        }

        if (is_float($value)) {
            // try to convert integral floats to integers
            if ($value == ($intValue = (int) $value)) {
                return $intValue;
            }
            return $value;
        }

        // try to convert integer-looking strings to integers
        if (is_string($value) &&
            ctype_digit($value) &&
            $value == (string) ($intValue = (int) $value)
        ) {
            return $intValue;
        }

        // convert other values to strings
        return (string) $value;
    }
}
