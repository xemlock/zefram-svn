<?php

set_include_path(
    realpath(dirname(__FILE__) . '/../../../')
    . PATH_SEPARATOR
);

require 'Zend/Loader/Autoloader.php';
Zend_Loader_Autoloader::getInstance()->registerNamespace('Zefram_');

function expect($id, Zefram_Validate_Between $validator, $value, $expect)
{
    $min = $validator->getMin();
    $max = $validator->getMax();
    $valid = $validator->isValid($value);

    printf("{min:%s, max:%s, inclusive:%d}, value: %s, valid: %d, expect: %d\t",
        isset($min) ? $min : 'NULL',
        isset($max) ? $max : 'NULL',
        $validator->getInclusive(),
        $value,
        $valid,
        $expect
    );

    if ($valid !== $expect) {
        echo "[ FAIL ]\n";
    } else {
        echo "[  OK  ]\n";
    }
    $messages = $validator->getMessages();
    if ($messages) {
        echo "\t", implode("\n\t", $messages), "\n";
    }
}

$validate = new Zefram_Validate_Between(array(
    'inclusive' => true,
));
expect(1, $validate, 0, true);
expect(2, $validate, 1, true);
expect(3, $validate, 2, true);
expect(4, $validate, 3, true);
expect(5, $validate, 4, true);

$validate = new Zefram_Validate_Between(array(
    'min' => 1,
    'inclusive' => true,
));
expect(6, $validate, 0, false);
expect(7, $validate, 1, true);
expect(8, $validate, 2, true);
expect(9, $validate, 3, true);
expect(10, $validate, 4, true);

$validate = new Zefram_Validate_Between(array(
    'max' => 3,
    'inclusive' => true,
));
expect(11, $validate, 0, true);
expect(12, $validate, 1, true);
expect(13, $validate, 2, true);
expect(14, $validate, 3, true);
expect(15, $validate, 4, false);

$validate = new Zefram_Validate_Between(array(
    'inclusive' => true,
    'min' => 1,
    'max' => 3,
));
expect(16, $validate, 0, false);
expect(17, $validate, 1, true);
expect(18, $validate, 2, true);
expect(19, $validate, 3, true);
expect(20, $validate, 4, false);

echo "inclusive = false\n";

$validate = new Zefram_Validate_Between(array(
    'inclusive' => false,
));
expect(1, $validate, 0, true);
expect(2, $validate, 1, true);
expect(3, $validate, 2, true);
expect(4, $validate, 3, true);
expect(5, $validate, 4, true);

$validate = new Zefram_Validate_Between(array(
    'min' => 1,
    'inclusive' => false,
));
expect(6, $validate, 0, false);
expect(7, $validate, 1, false);
expect(8, $validate, 2, true);
expect(9, $validate, 3, true);
expect(10, $validate, 4, true);

$validate = new Zefram_Validate_Between(array(
    'max' => 3,
    'inclusive' => false,
));
expect(11, $validate, 0, true);
expect(12, $validate, 1, true);
expect(13, $validate, 2, true);
expect(14, $validate, 3, false);
expect(15, $validate, 4, false);

$validate = new Zefram_Validate_Between(array(
    'inclusive' => false,
    'min' => 1,
    'max' => 3,
));
expect(16, $validate, 0, false);
expect(17, $validate, 1, false);
expect(18, $validate, 2, true);
expect(19, $validate, 3, false);
expect(20, $validate, 4, false);


echo "Inclusive implicit (true)\n";

$validate = new Zefram_Validate_Between;
expect(1, $validate, 0, true);
expect(2, $validate, 1, true);
expect(3, $validate, 2, true);
expect(4, $validate, 3, true);
expect(5, $validate, 4, true);

$validate = new Zefram_Validate_Between(array(
    'min' => 1,
));
expect(6, $validate, 0, false);
expect(7, $validate, 1, true);
expect(8, $validate, 2, true);
expect(9, $validate, 3, true);
expect(10, $validate, 4, true);

$validate = new Zefram_Validate_Between(array(
    'max' => 3,
));
expect(11, $validate, 0, true);
expect(12, $validate, 1, true);
expect(13, $validate, 2, true);
expect(14, $validate, 3, true);
expect(15, $validate, 4, false);

$validate = new Zefram_Validate_Between(array(
    'min' => 1,
    'max' => 3,
));
expect(16, $validate, 0, false);
expect(17, $validate, 1, true);
expect(18, $validate, 2, true);
expect(19, $validate, 3, true);
expect(20, $validate, 4, false);
