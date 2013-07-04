<?php

abstract class Zefram_Math_Rand
{
    /**
     * @return int
     */
    public static function getInteger($min = 0, $max = null)
    {
        if (null === $max) {
            $max = mt_getrandmax();
        }
        return mt_rand($min, $max);
    }

    /**
     * @return float
     */
    public static function getFloat()
    {
        return mt_rand() / mt_getrandmax();
    }

    /**
     * @return string
     */
    public static function getString($length, $chars = null)
    {
        if (null === $chars) {
            // if character list is not expicitly given, use URL-safe Base64
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
        }

        $randmax = strlen($chars) - 1;

        $length = max(0, $length);
        $output = '';

        while (strlen($output) < $length) {
            $output .= substr($chars, self::getInteger(0, $randmax), 1);
        }

        return $output;
    }
}
