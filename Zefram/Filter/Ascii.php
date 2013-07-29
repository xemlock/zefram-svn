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

        'ß' => 'ss',
        'ẞ' => 'SS',
        'þ' => 'th',
        'Þ' => 'Th',
        'Æ' => 'Ae',
        'æ' => 'ae',
        'Œ' => 'Oe',
        'œ' => 'oe',
        'Ð' => 'D',
        'ð' => 'd',

        'Á' => 'A', // acute
        'A̋' => 'A', // dbl acute
        'À' => 'A', // grave
        'Ȁ' => 'A', // dbl grave
        'Ă' => 'A', // breve
        'Ȃ' => 'A', // inv breve
        'Ǎ' => 'A', // caron/hacek
        'A̧' => 'A', // cedilla
        'Â' => 'A', // circumflex
        'Ä' => 'A', // umlaut
        'Ȧ' => 'A', // dot above
        'Ạ' => 'A', // dot below
        'Ā' => 'A', // macron
        'Ą' => 'A', // ogonek
        'Å' => 'A', // ring above
        'Ḁ' => 'A', // ring below
        'Ã' => 'A', // tilde

        'á' => 'a', // acute
        'a̋' => 'a', // dbl acute
        'à' => 'a', // grave
        'ȁ' => 'a', // dbl grave
        'ă' => 'a', // breve
        'ȃ' => 'a', // inv breve
        'ǎ' => 'a', // caron/hacek
        'a̧' => 'a', // cedilla
        'â' => 'a', // circumflex
        'ä' => 'a', // umlaut
        'ȧ' => 'a', // dot above
        'ạ' => 'a', // dot below
        'ā' => 'a', // macron
        'ą' => 'a', // ogonek
        'å' => 'a', // ring above
        'ḁ' => 'a', // ring below
        'ã' => 'a', // tilde

        'Ḃ' => 'B', // dot above
        'Ḅ' => 'B', // dot below
        'Ḇ' => 'B', // macron

        'ḃ' => 'b', // dot above
        'ḅ' => 'b', // dot below
        'ḇ' => 'b', // marcon

        'Ć' => 'C', // acute
        'Č' => 'C', // caron/hacek
        'Ç' => 'C', // cedilla
        'Ĉ' => 'C', // circumflex
        'C̈' => 'c', // umlaut
        'Ċ' => 'C', // dot above
        'C̄' => 'C', // macron

        'ć' => 'c', // acute
        'č' => 'c', // caron/hacek
        'ç' => 'c', // cedilla
        'ĉ' => 'c', // circumflex
        'c̈' => 'c', // umlaut
        'ċ' => 'c', // dot above
        'c̄' => 'c', // macron

        'Ď' => 'D', // caron/hacek
        'Ḑ' => 'D', // cedilla
        'Ḓ' => 'D', // circumflex
        'Ḋ' => 'D', // dot above
        'Ḍ' => 'D', // dot below
        'Ḏ' => 'D', // macron

        'ď' => 'd', // caron/hacek
        'ḑ' => 'd', // cedilla
        'ḓ' => 'd', // circumflex
        'ḋ' => 'd', // dot above
        'ḍ' => 'd', // dot below
        'ḏ' => 'd', // macron

        'É' => 'E', // acute
        'E̋' => 'E', // dbl acute 
        'È' => 'E', // grave
        'Ȅ' => 'E', // dbl grave
        'Ĕ' => 'E', // breve
        'Ȇ' => 'E', // inv breve
        'Ě' => 'E', // caron/hacek
        'Ȩ' => 'E', // cedilla
        'Ê' => 'E', // circumflex
        'Ḙ' => 'E', // circumflex (below)
        'Ë' => 'E', // umlaut
        'Ė' => 'E', // dot above
        'Ẹ' => 'E', // dot below
        'Ē' => 'E', // macron
        'Ę' => 'E', // ogonek
        'E̊' => 'E', // ring above
        'Ẽ' => 'E', // tilde
        'Ḛ' => 'E', // tilde (below)

        'é' => 'e', // acute
        'e̋' => 'e', // dbl acute 
        'è' => 'e', // grave
        'ȅ' => 'e', // dbl grave
        'ĕ' => 'e', // breve
        'ȇ' => 'e', // inv breve
        'ě' => 'e', // caron/hacek
        'ȩ' => 'e', // cedilla
        'ê' => 'e', // circumflex
        'ḙ' => 'e', // circumflex (below)
        'ë' => 'e', // umlaut
        'ė' => 'e', // dot above
        'ẹ' => 'e', // dot below
        'ē' => 'e', // macron
        'ę' => 'e', // ogonek
        'e̊' => 'e', // ring above
        'ẽ' => 'e', // tilde
        'ḛ' => 'e', // tilde (below)

        'F̌' => 'F', // caron/hacek
        'Ḟ' => 'F', // dot above

        'f̌' => 'f', // caron/hacek
        'ḟ' => 'f', // dot above

        'Ǵ' => 'G', // acute
        'Ğ' => 'G', // breve
        'Ǧ' => 'G', // caron/hacek
        'Ģ' => 'G', // cedilla
        'Ĝ' => 'G', // circumflex
        'Ġ' => 'G', // dot above
        'Ḡ' => 'G', // macron

        'ǵ' => 'g', // acute
        'ğ' => 'g', // breve
        'ǧ' => 'g', // caron/hacek
        'ģ' => 'g', // cedilla
        'ĝ' => 'g', // circumflex
        'ġ' => 'g', // dot above
        'ḡ' => 'g', // macron

        'Ḫ' => 'H', // breve
        'Ȟ' => 'H', // caron/hacek
        'Ḩ' => 'H', // cedilla
        'Ĥ' => 'H', // circumflex
        'Ḧ' => 'H', // umlaut
        'Ḣ' => 'H', // dot above
        'Ḥ' => 'H', // dot below
        'H̱' => 'H', // macron

        'ḫ' => 'h', // breve
        'ȟ' => 'h', // caron/hacek
        'ḩ' => 'h', // cedilla
        'ĥ' => 'h', // circumflex
        'ḧ' => 'h', // umlaut
        'ḣ' => 'h', // dot above
        'ḥ' => 'h', // dot below
        'ẖ' => 'h', // macron

        'Í' => 'I', // acute
        'Ì' => 'I', // grave
        'Ȉ' => 'I', // dbl grave
        'Ĭ' => 'I', // breve
        'Ȋ' => 'I', // inv breve
        'Ǐ' => 'I', // caron/hacek
        'I̧' => 'I', // cedilla
        'Î' => 'I', // circumflex
        'Ï' => 'I', // umlaut
        'İ' => 'I', // dot above
        'Ị' => 'I', // dot below
        'Ī' => 'I', // macron
        'Į' => 'I', // ogonek
        'Ĩ' => 'I', // tilde
        'Ḭ' => 'I', // tilde (below)

        'í' => 'i', // acute
        'ì' => 'i', // grave
        'ȉ' => 'i', // dbl grave
        'ĭ' => 'i', // breve
        'ȋ' => 'i', // inv breve
        'ǐ' => 'i', // caron/hacek
        'i̧' => 'i', // cedilla
        'î' => 'i', // circumflex
        'ï' => 'i', // umlaut
        'ị' => 'i', // dot below
        'ī' => 'i', // macron
        'į' => 'i', // ogonek
        'ĩ' => 'i', // tilde
        'ḭ' => 'i', // tilde (below)

        'Ł' => 'L',
        'ł' => 'l',
        'Ń' => 'N', 'Ñ' => 'N', 'Ň' => 'N',
        'ń' => 'n', 'ñ' => 'n', 'ň' => 'n',
        'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
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
