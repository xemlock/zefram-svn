<?php

/**
 * Modified Base64 for URL applications (base64url encoding)
 */
class Zefram_Crypt_Base64url
{
    public static function encode($string)
    {
        $encoded = base64_encode($string);
        $encoded = strtr($encoded, '+/', '-_');
        $encoded = rtrim($encoded, '=');
        return $encoded;
    }

    public static function decode($encoded)
    {
        $chunk_size = 4096; // multiple of 4
        $encoded = strtr($encoded, '-_', '+/');
        if (strlen($encoded) > $chunk_size) {          
            $decoded = array();
            // base64_decode sometimes has problems with strings containing ~5k characters.
            // To fix this split the encoded string into substrings counting modulo 4 chars,
            // then decode each substring and concatenate all of them.
            for ($i = 0, $n = ceil(strlen($encoded) / $chunk_size); $i < $n; ++$i) {
                $decoded_chunk = @base64_decode(substr($encoded, $i * $chunk_size, $chunk_size));
                if (false === $decoded_chunk) {
                    return false;
                }
                $decoded[] = $decoded_chunk;
            }
            return implode('', $decoded);
        }
        return @base64_decode($encoded);
    }
}
