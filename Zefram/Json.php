<?php

/**
 * Wrapper around Zend_Json class for more flexible JSON encoding / decoding.
 *
 * @package Zefram_Json
 * @uses    Zend_Json
 */
abstract class Zefram_Json
{
    const TYPE_ARRAY  = Zend_Json::TYPE_ARRAY;
    const TYPE_OBJECT = Zend_Json::TYPE_OBJECT;

    const CYCLE_CHECK       = 'cycleCheck';
    const PRETTY_PRINT      = 'prettyPrint';
    const UNESCAPED_SLASHES = 'unescapedSlashes';
    const UNESCAPED_UNICODE = 'unescapedUnicode';
    const HEX_TAG           = 'hexTag';
    const HEX_QUOT          = 'hexQuot';

    /**
     * @param string $encodedValue
     * @param int $objectDecodeType
     * @return mixed
     */
    public static function decode($encodedValue, $objectDecodeType = self::TYPE_ARRAY)
    {
        return Zend_Json::decode($encodedValue, $objectDecodeType);
    }

    /**
     * @param mixed $value
     * @param array $options
     */
    public static function encode($value, array $options = array())
    {
        $requirePhp53 = false;
        $requirePhp54 = false;

        // cycle check applies only when encoding using Zend_Json_Encoder,
        // json_encode() has built-in recursion limit
        if (isset($options[self::CYCLE_CHECK])) {
            $cycleCheck = (bool) $options[self::CYCLE_CHECK];
            unset($options[self::CYCLE_CHECK]);
        } else {
            $cycleCheck = false;
        }

        if (isset($options[self::HEX_TAG])) {
            $hexTag = (bool) $options[self::HEX_TAG];
            $requirePhp53 = true;
            unset($options[self::HEX_TAG]);
        } else {
            $hexTag = false;
        }

        if (isset($options[self::HEX_QUOT])) {
            $hexQuot = (bool) $options[self::HEX_QUOT];
            $requirePhp53 = true;
            unset($options[self::HEX_QUOT]);
        } else {
            $hexQuot = false;
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

        $minVersion = $requirePhp54 ? '5.4.0' : ($requirePhp53 ? '5.3.0' : 0);
        $useNative = extension_loaded('json')
            && (!$minVersion || version_compare(PHP_VERSION, $minVersion, '>='));

        if ($useNative) {
            $flags = 0
                | ($hexTag ? JSON_HEX_TAG : 0)
                | ($hexQuot ? JSON_HEX_QUOT : 0)
                | ($unescapedSlashes ? JSON_UNESCAPED_SLASHES : 0)
                | ($unescapedUnicode ? JSON_UNESCAPED_UNICODE : 0)
                | ($prettyPrint ? JSON_PRETTY_PRINT : 0);

            $json = json_encode($value, $flags);

        } else {
            $json = Zend_Json::encode($value, $cycleCheck, $options);

            $search = array();
            $replace = array();

            if ($hexTag) {
                $search[]  = '<';
                $replace[] = '\u003C';

                $search[]  = '>';
                $replace[] = '\u003E';
            }

            if ($hexQuot) {
                $search[]  = '"';
                $replace[] = '\u0022';
            }

            if ($unescapedSlashes) {
                $search[]  = '\\/';
                $replace[] = '/';
            }

            if ($search) {
                $json = str_replace($search, $replace, $json);
            }

            if ($unescapedUnicode) {
                $json = Zend_Json_Decoder::decodeUnicodeString($json);
            }

            if ($prettyPrint) {
                $json = self::prettyPrint($json);
            }
        }

        // No string in JavaScript can contain a literal U+2028 or a U+2029
        // (line terminator and paragraph terminator respectively), so remove
        // them from the encoded string. Read more:
        // http://timelessrepo.com/json-isnt-a-javascript-subset
        $json = strtr(
            $json,
            array(
                "\xE2\x80\xA8" => '', // \u2028
                "\xE2\x80\xA9" => '', // \u2029
            )
        );

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
            Zend_Json::prettyPrint($json, array(
                'format' => 'txt',
                'indent' => '    ',
            ))
        );
    }
}
