<?php

abstract class Zefram_Stdlib_ArrayUtils
{
    const CASE_LOWER      = 0;
    const CASE_UPPER      = 1;
    const CASE_CAMEL      = 2;
    const CASE_UNDERSCORE = 4;

    /**
     * Changes the case of all keys in an array.
     *
     * @param  array $array
     * @param  int $case
     * @return array|false
     */
    public static function changeKeyCase(array $array, $case = self::CASE_LOWER)
    {
        $case = (int) $case;

        if ($case === self::CASE_LOWER || $case === self::CASE_UPPER) {
            return array_change_key_case($array, $case);
        }

        // underscore to camelcase
        if ($case & self::CASE_CAMEL) {
            $result = array();
            foreach ($array as $key => $value) {
                $key = strtolower($key);
                $key = str_replace('_', ' ', $key);
                $key = ucwords($key);
                $key = str_replace(' ', '', $key);
                $result[$key] = $value;
            }
            return $result;
        }

        // camelcase to underscore
        if ($case & self::CASE_UNDERSCORE) {
            $toupper = $case & self::CASE_UPPER;
            $result = array();
            foreach ($array as $key => $value) {
                $key = preg_replace('/([0-9a-z])([A-Z])/', '$1_$2', $key);
                $key = $toupper ? strtoupper($key) : strtolower($key);
                $result[$key] = $value;
            }
            return $result;
        }

        return false;
    }


}
