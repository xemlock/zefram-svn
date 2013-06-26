<?php

abstract class Zefram_Json extends Zend_Json
{
    const CYCLE_CHECK       = 'cycleCheck';
    const PRETTY_PRINT      = 'prettyPrint';
    const UNESCAPED_SLASHES = 'unescapedSlashes';
    const UNESCAPED_UNICODE = 'unescapedUnicode';

    /**
     * @param mixed $value
     * @param array $options
     */
    public static function encode($value, array $options = array())
    {
        $requirePhp54 = false;

        // cycle check applies only when encoding using Zend_Json_Encoder,
        // json_encode() has built-in recursion limit
        if (isset($options[self::CYCLE_CHECK])) {
            $cycleCheck = (bool) $options[self::CYCLE_CHECK];
            unset($options[self::CYCLE_CHECK]);
        } else {
            $cycleCheck = false;
        }

        if (isset($options[self::PRETTY_PRINT])) {
            $prettyPrint = (bool) $options[self::PRETTY_PRINT];
            $requirePhp54 = true;
            unset($options[self::PRETTY_PRINT]);
        } else {
            $prettyPrint = false;
        }

        if (isset($options[self::UNESCAPED_SLASHES])) {
            $unescapedSlashes = (bool) $options[self::UNESCAPED_SLASHES];
            $requirePhp54 = true;
            unset($options[self::UNESCAPED_SLASHES]);
        } else {
            $unescapedSlashes = false;
        }

        if (isset($options[self::UNESCAPED_UNICODE])) {
            $unescapedUnicode = (bool) $options[self::UNESCAPED_UNICODE];
            $requirePhp54 = true;
            unset($options[self::UNESCAPED_UNICODE]);
        } else {
            $unescapedUnicode = false;
        }

        $useNative = extension_loaded('json') 
            && (!$requirePhp54 || version_compare(PHP_VERSION, '5.4.0', '>='));

        if ($useNative) {
            $flags = 0
                | ($unescapedSlashes ? JSON_UNESCAPED_SLASHES : 0)
                | ($unescapedUnicode ? JSON_UNESCAPED_UNICODE : 0)
                | ($prettyPrint ? JSON_PRETTY_PRINT : 0);

            return json_encode($value, $flags);
        }

        $json = parent::encode($value, $cycleCheck, $options);

        if ($unescapedSlashes) {
            $json = str_replace('\\/', '/', $json);
        }

        if ($unescapedUnicode) {
            $json = Zend_Json_Decoder::decodeUnicodeString($json);
        }

        if ($prettyPrint) {
            $json = self::prettyPrint($json);
        }

        return $json;
    }

    /**
     * Pretty-print JSON string the same way as json_encode() function called
     * with JSON_PRETTY_PRINT flag would do.
     *
     * @param string $json
     * @return string
     */
    public static function prettyPrint($json)
    {
        return preg_replace(
            '/(?<!\\\\)":(["\[\{]|\d)/',
            '": \1',
            parent::prettyPrint($json, array(
                'format' => 'txt',
                'indent' => '    ',
            ))
        );
    }
}
