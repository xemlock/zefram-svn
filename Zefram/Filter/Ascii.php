<?php

class Zefram_Filter_Ascii implements Zend_Filter_Interface
{
    /**
     * Static character transliteration table.
     * @var array
     */
    protected static $_charmap = array(
        '¡' => '!',
        '¿' => '?',
        '–' => '-',
        '—' => '-',
        '’' => "'",
        '‘' => "'",
        '“' => '"',
        '”' => '"',
        '„' => '"',
        '…' => '...',
        '©' => '(c)',

        'ß' => 'ss',
        'ẞ' => 'SS',
        'þ' => 'th',
        'Þ' => 'Th',
        'Ð' => 'D',
        'ð' => 'd',
        'ĸ' => 'q', // greenlandic letter Kra

        'Æ' => 'AE',
        'Ǽ' => 'AE',
        'Ǣ' => 'AE',
        'æ' => 'ae',
        'ǽ' => 'ae',
        'ǣ' => 'ae',
        'Œ' => 'OE',
        'œ' => 'oe',
        'ᵫ' => 'ue',
        'Ĳ' => 'IJ',
        'ĳ' => 'ij',

        // Ệ     ệ

        // A (29)
        'Á' => 'A', // acute
        'A̋' => 'A', // double acute
        'À' => 'A', // grave
        'Ȁ' => 'A', // double grave
        'Ă' => 'A', // breve
        'Ắ' => 'A', // breve and acute
        'Ằ' => 'A', // breve and grave
        'Ặ' => 'A', // breve and dot below
        'Ẳ' => 'A', // breve and hook above
        'Ẵ' => 'A', // breve and tilde
        'Ȃ' => 'A', // inv breve
        'Ǎ' => 'A', // caron/hacek
        'A̧' => 'A', // cedilla
        'Â' => 'A', // circumflex
        'Ấ' => 'A', // circumflex and acute
        'Ầ' => 'A', // circumflex and grave
        'Ẫ' => 'A', // circumflex and tilde
        'Ậ' => 'A', // circumflex and dot below
        'Ä' => 'A', // umlaut
        'Ǟ' => 'A', // umlaut and macron
        'Ȧ' => 'A', // dot above
        'Ǡ' => 'A', // dot above and macron
        'Ạ' => 'A', // dot below
        'Ā' => 'A', // macron
        'Ą' => 'A', // ogonek
        'Å' => 'A', // ring above
        'Ǻ' => 'A', // ring above and acute
        'Ḁ' => 'A', // ring below
        'Ã' => 'A', // tilde

        // a (29)
        'á' => 'a', // acute
        'a̋' => 'a', // double acute
        'à' => 'a', // grave
        'ȁ' => 'a', // double grave
        'ă' => 'a', // breve
        'ắ' => 'a', // breve and acute
        'ằ' => 'a', // breve and grave
        'ặ' => 'a', // breve and dot below
        'ẳ' => 'a', // breve and hook above
        'ẵ' => 'a', // breve and tilde
        'ȃ' => 'a', // inv breve
        'ǎ' => 'a', // caron/hacek
        'a̧' => 'a', // cedilla
        'â' => 'a', // circumflex
        'ấ' => 'a', // circumflex and acute
        'ầ' => 'a', // circumflex and grave
        'ẫ' => 'a', // circumflex and tilde
        'ậ' => 'a', // circumflex and dot below
        'ä' => 'a', // umlaut
        'ǟ' => 'a', // umlaut and macron
        'ȧ' => 'a', // dot above
        'ǡ' => 'a', // dot above and macron
        'ạ' => 'a', // dot below
        'ā' => 'a', // macron
        'ą' => 'a', // ogonek
        'å' => 'a', // ring above
        'ǻ' => 'a', // ring above and acute
        'ḁ' => 'a', // ring below
        'ã' => 'a', // tilde

        // B (3)
        'Ḃ' => 'B', // dot above
        'Ḅ' => 'B', // dot below
        'Ḇ' => 'B', // macron

        // b (3)
        'ḃ' => 'b', // dot above
        'ḅ' => 'b', // dot below
        'ḇ' => 'b', // marcon

        // C (7)
        'Ć' => 'C', // acute
        'Č' => 'C', // caron/hacek
        'Ç' => 'C', // cedilla
        'Ĉ' => 'C', // circumflex
        'C̈' => 'c', // umlaut
        'Ċ' => 'C', // dot above
        'C̄' => 'C', // macron

        // c (7)
        'ć' => 'c', // acute
        'č' => 'c', // caron/hacek
        'ç' => 'c', // cedilla
        'ĉ' => 'c', // circumflex
        'c̈' => 'c', // umlaut
        'ċ' => 'c', // dot above
        'c̄' => 'c', // macron

        // D (6)
        'Ď' => 'D', // caron/hacek
        'Ḑ' => 'D', // cedilla
        'Ḓ' => 'D', // circumflex
        'Ḋ' => 'D', // dot above
        'Ḍ' => 'D', // dot below
        'Ḏ' => 'D', // macron

        // d (6)
        'ď' => 'd', // caron/hacek
        'ḑ' => 'd', // cedilla
        'ḓ' => 'd', // circumflex
        'ḋ' => 'd', // dot above
        'ḍ' => 'd', // dot below
        'ḏ' => 'd', // macron

        // E (21)
        'É' => 'E', // acute
        'E̋' => 'E', // double acute
        'È' => 'E', // grave
        'Ȅ' => 'E', // double grave
        'Ĕ' => 'E', // breve
        'Ȇ' => 'E', // inv breve
        'Ě' => 'E', // caron/hacek
        'Ȩ' => 'E', // cedilla
        'Ḝ' => 'E', // cedilla and breve
        'Ê' => 'E', // circumflex
        'Ḙ' => 'E', // circumflex below
        'Ë' => 'E', // umlaut
        'Ė' => 'E', // dot above
        'Ẹ' => 'E', // dot below
        'Ē' => 'E', // macron
        'Ḗ' => 'E', // macron and acute
        'Ḕ' => 'E', // macron and grave
        'Ę' => 'E', // ogonek
        'E̊' => 'E', // ring above
        'Ẽ' => 'E', // tilde
        'Ḛ' => 'E', // tilde below

        // e (21)
        'é' => 'e', // acute
        'e̋' => 'e', // double acute
        'è' => 'e', // grave
        'ȅ' => 'e', // double grave
        'ĕ' => 'e', // breve
        'ȇ' => 'e', // inv breve
        'ě' => 'e', // caron/hacek
        'ȩ' => 'e', // cedilla
        'ḝ' => 'e', // cedilla and breve
        'ê' => 'e', // circumflex
        'ḙ' => 'e', // circumflex below
        'ë' => 'e', // umlaut
        'ė' => 'e', // dot above
        'ẹ' => 'e', // dot below
        'ē' => 'e', // macron
        'ḗ' => 'e', // macron and acute
        'ḕ' => 'e', // macron and grave
        'ę' => 'e', // ogonek
        'e̊' => 'e', // ring above
        'ẽ' => 'e', // tilde
        'ḛ' => 'e', // tilde below

        // F (2)
        'F̌' => 'F', // caron/hacek
        'Ḟ' => 'F', // dot above

        // f (2)
        'f̌' => 'f', // caron/hacek
        'ḟ' => 'f', // dot above

        // G (7)
        'Ǵ' => 'G', // acute
        'Ğ' => 'G', // breve
        'Ǧ' => 'G', // caron/hacek
        'Ģ' => 'G', // cedilla
        'Ĝ' => 'G', // circumflex
        'Ġ' => 'G', // dot above
        'Ḡ' => 'G', // macron

        // g (7)
        'ǵ' => 'g', // acute
        'ğ' => 'g', // breve
        'ǧ' => 'g', // caron/hacek
        'ģ' => 'g', // cedilla
        'ĝ' => 'g', // circumflex
        'ġ' => 'g', // dot above
        'ḡ' => 'g', // macron

        // H (8)
        'Ḫ' => 'H', // breve
        'Ȟ' => 'H', // caron/hacek
        'Ḩ' => 'H', // cedilla
        'Ĥ' => 'H', // circumflex
        'Ḧ' => 'H', // umlaut
        'Ḣ' => 'H', // dot above
        'Ḥ' => 'H', // dot below
        'H̱' => 'H', // macron

        // h (8)
        'ḫ' => 'h', // breve
        'ȟ' => 'h', // caron/hacek
        'ḩ' => 'h', // cedilla
        'ĥ' => 'h', // circumflex
        'ḧ' => 'h', // umlaut
        'ḣ' => 'h', // dot above
        'ḥ' => 'h', // dot below
        'ẖ' => 'h', // macron

        // I (15)
        'Í' => 'I', // acute
        'Ì' => 'I', // grave
        'Ȉ' => 'I', // double grave
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
        'Ḭ' => 'I', // tilde below

        // i (14)
        'í' => 'i', // acute
        'ì' => 'i', // grave
        'ȉ' => 'i', // double grave
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
        'ḭ' => 'i', // tilde below

        // J (2)
        'J̌' => 'J', // caron/hacek
        'Ĵ' => 'J', // circumflex

        // j (2)
        'ǰ' => 'j', // caron/hacek
        'ĵ' => 'j', // circumflex

        // K (5)
        'Ḱ' => 'K', // acute
        'Ǩ' => 'K', // caron/hacek
        'Ķ' => 'K', // cedilla
        'Ḳ' => 'K', // dot below
        'Ḵ' => 'K', // macron

        // k (5)
        'ḱ' => 'k', // acute
        'ǩ' => 'k', // caron
        'ķ' => 'k', // cedilla
        'ḳ' => 'k', // dot below
        'ḵ' => 'k', // macron

        // L (8)
        'Ĺ' => 'L', // acute
        'Ľ' => 'L', // caron
        'Ļ' => 'L', // cedilla
        'Ḽ' => 'L', // circumflex
        'Ḷ' => 'L', // dot below
        'Ḹ' => 'L', // dot below and macron
        'Ḻ' => 'L', // macron
        'Ł' => 'L', // stroke

        // l (8)
        'ĺ' => 'l', // acute
        'ľ' => 'l', // caron
        'ļ' => 'l', // cedilla
        'ḽ' => 'l', // circumflex
        'ḷ' => 'l', // dot below
        'ḹ' => 'l', // dot below and macron
        'ḻ' => 'l', // macron
        'ł' => 'l', // stroke

        // M (6)
        'Ḿ' => 'M', // acute
        'M̧' => 'M', // cedilla
        'M̈' => 'M', // umlaut
        'Ṁ' => 'M', // dot above
        'Ṃ' => 'M', // dot below
        'M̄' => 'm', // macron

        // m (6)
        'ḿ' => 'm', // acute
        'm̧' => 'm', // cedilla
        'm̈' => 'm', // umlaut
        'ṁ' => 'm', // dot above
        'ṃ' => 'm', // dot below
        'm̄' => 'm', // macron

        // N (11)
        'Ń' => 'N', // acute
        'Ǹ' => 'N', // grave
        'Ň' => 'N', // caron/hacek
        'Ņ' => 'N', // cedilla
        'Ṋ' => 'N', // circumflex
        'N̈' => 'N', // umlaut
        'Ṅ' => 'N', // dot above
        'Ṇ' => 'N', // dot below
        'N̄' => 'N', // macron
        'Ṉ' => 'N', // macron below
        'Ñ' => 'N', // tilde

        // n (11)
        'ń' => 'n', // acute
        'ǹ' => 'n', // grave
        'ň' => 'n', // caron/hacek
        'ņ' => 'n', // cedilla
        'ṋ' => 'n', // circumflex
        'n̈' => 'n', // umlaut
        'ṅ' => 'n', // dot above
        'ṇ' => 'n', // dot below
        'n̄' => 'n', // macron
        'ṉ' => 'n', // macron below
        'ñ' => 'n', // tilde

        // O (36)
        'Ó' => 'O', // acute
        'Ő' => 'O', // double acute
        'Ò' => 'O', // grave
        'Ȍ' => 'O', // double grave
        'Ŏ' => 'O', // breve
        'Ȏ' => 'O', // inv breve
        'Ǒ' => 'O', // caron/hacek
        'O̧' => 'O', // cedilla
        'Ô' => 'O', // circumflex
        'Ố' => 'O', // circumflex and acute
        'Ồ' => 'O', // circumflex and grave
        'Ổ' => 'O', // circumflex and hook above
        'Ộ' => 'O', // circumflex and dot below
        'Ỗ' => 'O', // circumflex and tilde
        'Ö' => 'O', // umlaut
        'Ȫ' => 'O', // umlaut and macron
        'Ȯ' => 'O', // dot above
        'Ȱ' => 'O', // dot above and macron
        'Ọ' => 'O', // dot below
        'Ỏ' => 'O', // hook above
        'Ơ' => 'O', // horn
        'Ớ' => 'O', // horn and acute
        'Ờ' => 'O', // horn and grave
        'Ợ' => 'O', // horn and dot below
        'Ở' => 'O', // horn and hook above
        'Ỡ' => 'O', // horn and tilde
        'Ō' => 'O', // macron
        'Ṓ' => 'O', // macron and acute
        'Ṑ' => 'O', // macron and grave
        'Ǫ' => 'O', // ogonek
        'Ǭ' => 'O', // ogonek and macron
        'Ø' => 'O', // stroke
        'Õ' => 'O', // tilde
        'Ṍ' => 'O', // tilde and acute
        'Ṏ' => 'O', // tilde and umlaut
        'Ȭ' => 'O', // tilde and macron

        // o (36)
        'ó' => 'o', // acute
        'ő' => 'o', // double acute
        'ò' => 'o', // grave
        'ȍ' => 'o', // double grave
        'ŏ' => 'o', // breve
        'ȏ' => 'o', // inv breve
        'ǒ' => 'o', // caron/hacek
        'o̧' => 'o', // cedilla
        'ô' => 'o', // circumflex
        'ồ' => 'o', // circumflex and grave
        'ố' => 'o', // circumflex and acute
        'ổ' => 'o', // circumflex and hook above
        'ộ' => 'o', // circumflex and dot below
        'ỗ' => 'o', // circumflex and tilde
        'ö' => 'o', // umlaut
        'ȫ' => 'o', // umlaut and macron
        'ȯ' => 'o', // dot above
        'ȱ' => 'o', // dot above and macron
        'ọ' => 'o', // dot below
        'ỏ' => 'o', // hook above
        'ơ' => 'o', // horn
        'ớ' => 'o', // horn and acute
        'ờ' => 'o', // horn and grave
        'ợ' => 'o', // horn and dot below
        'ở' => 'o', // horn and hook above
        'ỡ' => 'o', // horn and tilde
        'ō' => 'o', // macron
        'ṓ' => 'o', // macron and acute
        'ṑ' => 'o', // macron and grave
        'ǫ' => 'o', // ogonek
        'ǭ' => 'o', // ogonek and macron
        'ø' => 'o', // stroke
        'õ' => 'o', // tilde
        'ṍ' => 'o', // tilde and acute
        'ṏ' => 'o', // tilde and umlaut
        'ȭ' => 'o', // tilde and macron

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
