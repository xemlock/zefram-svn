<?php

/**
 * This is an implementation of base64url encoder/decoder as described
 * in Section 5 of RFC 4648, "Base 64 Encoding with URL and Filename
 * Safe Alphabet"
 */
class Zefram_Filter_Encode_Base64url extends Zefram_Filter_Encode_Base64
{
    public function toString()
    {
        return 'Base64url';
    }

    public function encode($value)
    {
        $encoded = parent::encode($value);
        $encoded = strtr($encoded, '+/', '-_');
        return $encoded;
    }

    public function decode($encoded)
    {
        $encoded = strtr($encoded, '-_', '+/');
        return parent::decode($encoded);
    }
}
