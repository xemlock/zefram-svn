<?php

class Zefram_Filter_Ascii implements Zend_Filter_Interface
{
    /**
     * Static character transliteration table.
     * @var array
     */
    protected static $_charmap = array(
        '¡' => '!', '¿' => '?',
        '–' => '-', '—' => '-',
        '’' => "'", '‘' => "'",
        '“' => '"', '”' => '"', '„' => '"',
        '…' => '...',
        '©' => '(c)',

        'µ' => 'u',
        'ß' => 'ss',
        'ẞ' => 'SS',
        'þ' => 'th',
        'Þ' => 'Th',
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ā' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'Ae',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ā' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae',
        'Ą' => 'A',
        'ą' => 'a',
        'Č' => 'C',
        'č' => 'c',
        'Ç' => 'C',
        'ç' => 'c',
        'Ď' => 'D', 'Ð' => 'D',
        'ď' => 'd', 'ð' => 'd',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ē' => 'E', 'Ë' => 'E', 'Ě' => 'E', 'Ė' => 'E',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ē' => 'e', 'ë' => 'e', 'ě' => 'e', 'ė' => 'e',
        'Ğ' => 'G', 'Ĝ' => 'G', 'Ǧ' => 'G', 'Ḡ' => 'G', 'Ġ' => 'g', 'Ǵ' => 'G', 'Ģ' => 'G',
        'ğ' => 'g', 'ĝ' => 'g', 'ǧ' => 'g', 'ḡ' => 'g', 'ġ' => 'g', 'ǵ' => 'g', 'ģ' => 'g',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'İ' => 'I', 'Ī' => 'I',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ı' => 'i', 'ī' => 'i',
        'Ł' => 'L',
        'ł' => 'l',
        'Ń' => 'N', 'Ñ' => 'N', 'Ň' => 'N',
        'ń' => 'n', 'ñ' => 'n', 'ň' => 'n',
        'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Œ' => 'Oe',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'œ' => 'oe',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ū' => 'U', 'Ü' => 'U', 'Ů' => 'U',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ū' => 'u', 'ü' => 'u', 'ů' => 'u',
        'Ř' => 'R',
        'ř' => 'r',
        'Ś' => 's', 'Š' => 'S',
        'ś' => 's', 'š' => 's',
        'Ş' => 'S',
        'ş' => 's',
        'Ť' => 'T',
        'ť' => 't',
        'Ž' => 'Z',
        'Ý' => 'Y', 'Ÿ' => 'Y', '¥' => 'Y',
        'ý' => 'y', 'ÿ' => 'y',
        'Ź' => 'Z', 'Ż' => 'Z', 'Ž' => 'Z',
        'ź' => 'z', 'ż' => 'z', 'ž' => 'z',

        // Greek
        'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
        'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
        'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
        'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
        'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',
        'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
        'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
        'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
        'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
        'Ϋ' => 'Y',

        // Cyrillic
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
        'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
        'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
        'я' => 'ya',
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
        'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
        'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
        'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
        'Я' => 'Ya',

        // Ukraininan cyrillic
        'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
        'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',

    	'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
        'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N',
	    'į' => 'i', 'ų' => 'u',
        'Į' => 'I', 'Ų' => 'U',
    );

    /**
     * Reliably transliterate given string to ASCII-only characters.
     *
     * @param string $string
     * @return string
     */
    public function filter($string)
    {
        // iconv() is heavily dependent on its implementation on the user
        // system and in its current form has a lot of inconsistencies.
        // The transliteration done by iconv is not consistent across
        // implementations. For instance, the glibc implementation 
        // transliterates é into e, but libiconv transliterates it into 'e.
        // See: http://stackoverflow.com/questions/5048401/why-doesnt-translit-work#answer-5048939

        $string = strtr($string, self::$_charmap);

        // iconv is completely unreliable across platforms
        /*
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
        */

        return $string;
    }
}
