<?php

abstract class Zefram_Math_Rand
{
    const ALPHA     = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    const ALNUM     = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const DIGITS    = '0123456789';
    const XDIGITS   = '0123456789ABCDEFabcdef';
    const BASE64    = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    const BASE64URL = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';

    /**
     * @param  int $min
     * @param  int $max OPTIONAL
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
     * @param  int $length
     * @param  string $chars OPTIONAL   if character list is not expicitly
     *                                  given, use URL-safe Base64 alphabet
     * @return string
     */
    public static function getString($length, $chars = self::BASE64URL)
    {
        $randmax = strlen($chars) - 1;

        $length = max(0, $length);
        $output = '';

        while (strlen($output) < $length) {
            $output .= substr($chars, self::getInteger(0, $randmax), 1);
        }

        return $output;
    }
}
