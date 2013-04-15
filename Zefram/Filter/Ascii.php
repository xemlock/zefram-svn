<?php

class Zefram_Filter_Ascii implements Zend_Filter_Interface
{
    public function filter($string)
    {
        
        // The transliteration done by iconv is not consistent across
        // implementations. For instance, the glibc implementation 
        // transliterates é into e, but libiconv transliterates it into 'e.
        // See: http://stackoverflow.com/questions/5048401/why-doesnt-translit-work#answer-5048939

        $string = str_replace(
            array("æ",  "Æ",   "ß",  "þ",  "Þ", "–", "—", "’", "‘", "“", "”", "„"),
            array("ae", "Ae", "ss", "th", "Th", "-", "-", "'", "'", "\"", "\"", "\""), 
            $string
        );

        if (ICONV_IMPL === 'glibc') {
            $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
        } else {
            // based on http://smoku.net/artykuly/zend-filter-ascii
            $string = iconv('UTF-8', 'WINDOWS-1250//TRANSLIT//IGNORE', $string);
            $string = strtr($string,
                "\xa5\xa3\xbc\x8c\xa7\x8a\xaa\x8d\x8f\x8e\xaf\xb9\xb3\xbe"
              . "\x9c\x9a\xba\x9d\x9f\x9e\xbf\xc0\xc1\xc2\xc3\xc4\xc5\xc6"
              . "\xc7\xc8\xc9\xca\xcb\xcc\xcd\xce\xcf\xd0\xd1\xd2\xd3\xd4"
              . "\xd5\xd6\xd7\xd8\xd9\xda\xdb\xdc\xdd\xde\xdf\xe0\xe1\xe2"
              . "\xe3\xe4\xe5\xe6\xe7\xe8\xe9\xea\xeb\xec\xed\xee\xef\xf0"
              . "\xf1\xf2\xf3\xf4\xf5\xf6\xf8\xf9\xfa\xfb\xfc\xfd\xfe",
                "ALLSSSSTZZZallssstzzzRAAAALCCCEEEEIIDDNNOOOOxRUUUUYT"
              . "sraaaalccceeeeiiddnnooooruuuuyt");
        }

        return $string;
    }
}
