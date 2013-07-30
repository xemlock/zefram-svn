<?php

$iconv_impl = trim(ICONV_IMPL, '"'); // "libiconv" with quotes!!!

$data = json_decode(file_get_contents('latin_diacritics.json', true));

$translit = "<?php\n\nreturn array(\n";

foreach ($data as $entry) {
    list($utf8, $name) = $entry;
    $res = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $utf8);
    $asc = substr($name, 0, 1);
    if ($res != $asc) {
        // echo $utf8, ' -> ', $res, ' expect: ', $asc, ' ... ', '[ FAIL ]', $name, "\n";
    } else {
        $translit .= "    '$utf8' => '$asc', // $name\n";
    }
}
$translit .= ");\n";

$output = "translit.$iconv_impl.php";
file_put_contents($output, $translit);
echo 'Output written to ', $output, "\n";
