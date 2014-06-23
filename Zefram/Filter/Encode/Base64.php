<?php

class Zefram_Filter_Encode_Base64 implements Zefram_Filter_Encode_Interface
{
    public function toString()
    {
        return 'Base64';
    }

    /**
     * @param  string $value
     * @return string
     */
    public function encode($value)
    {
        $encoded = base64_encode($value);
        $encoded = rtrim($encoded, '=');
        return $encoded;
    }

    /**
     * @param  string $encoded
     * @return string
     */
    public function decode($encoded)
    {
        $chunk_size = 4096; // multiple of 4
        if (strlen($encoded) > $chunk_size) {          
            $decoded = array();
            // base64_decode() sometimes has problems with strings containing
            // ~5k characters. To fix this the encoded string is split into
            // substrings counting modulo 4 characters, then each substring
            // is decoded separately. Resulting string is a concatenation of
            // all decoded substrings.
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
