<?php

set_include_path(
    realpath('./../../../') . PATH_SEPARATOR .
    realpath('./../../../ZendFramework-1.12.3/library') . PATH_SEPARATOR .
    get_include_path()
);

echo "\nZefram_Db_Row tests\n", str_repeat('-', 79), "\n\n";
echo 'Include path: ', get_include_path(), "\n\n";

require 'Zend/Loader/Autoloader.php';
Zend_Loader_Autoloader::getInstance()->registerNamespace('Zefram_');

$tmpdir = Zefram_Os::getTempDir();

$dbname = $tmpdir . '/test-' . mt_rand() . '.db';
$db = Zend_Db::factory('PDO_SQLITE', compact('dbname'));

echo "Database: ", $dbname, "\n\n";

echo "Tests:\n";

class ATable extends Zefram_Db_Table
{
    protected $_name = 'a';

    protected $_primary = 'a_id';

    protected $_sequence = true;

    protected $_referenceMap = array(
        'B' => array(
            'columns'       => 'b_id',
            'refTableClass' => 'BTable',
            'refColumns'    => 'b_id',
        ),
    );
}

class BTable extends Zefram_Db_Table
{
    protected $_name = 'b';

    protected $_primary = 'b_id';

    protected $_sequence = true;

    protected $_referenceMap = array(
        'A' => array(
            'columns'       => 'a_id',
            'refTableClass' => 'ATable',
            'refColumns'    => 'a_id',
        ),
    );

    protected $_rowClass = 'BTableRow';
}

class BTableRow extends Zefram_Db_Table_Row
{
    protected $_tableClass = 'BTable';

    protected function _postLoad()
    {
        self::$_postLoadLog[] = self::postLoadLogEntry($this);
    }

    protected static $_postLoadLog = array();

    public static function postLoadLogEntry(BTableRow $row)
    {
        return __METHOD__ . '(' . $row->b_id . ')';
    }

    public static function getPostLoadLog()
    {
        return (array) self::$_postLoadLog;
    }

    public static function clearPostLoadLog()
    {
        self::$_postLoadLog = array();
    }
}

$db->query('CREATE TABLE a (a_id INTEGER NOT NULL PRIMARY KEY, aval VARCHAR(32) NOT NULL, b_id INTEGER)');
$db->query('CREATE TABLE b (b_id INTEGER NOT NULL PRIMARY KEY, bval VARCHAR(32) NOT NULL, a_id INTEGER REFERENCES a (a_id))');

$tableProvider = new Zefram_Db_TableProvider($db);

$aTable = $tableProvider->getTable('ATable');
$bTable = $tableProvider->getTable('BTable');

$a = $aTable->createRow();
$a->aval = md5(mt_rand());
$a->save();

$a2 = $aTable->createRow();
$a2->aval = md5(mt_rand());
$a2->save();

$b = $bTable->createRow();
$b->A = $a;

assertTrue($b->A === $a,         'Parent row assignment ($b->A === $a)');
assertTrue($b->a_id == $a->a_id, 'Reference columns must match ($b->a_id == $a->a_id)');

$b2 = $bTable->createRow();
$b2->bval = md5(mt_rand());
$b2->save();
assertTrue($b2->A === null,      'Referenced parent is empty ($b2->A === null)');
assertTrue($b2->a_id === null,   'Referencing column is empty ($b2->a_id === null)');

$b2->A = $a;
$b2->save();

$b2->A = $a2;
$b2->save();

$b2->A = null;
$b2->save();

assertTrue($b2->A === null,     'NULL parent row assignment ($b2->A === null)');

$b2->A = null;
$b2->a_id = $a2->a_id;
assertTrue($b2->A && $b2->A->a_id == $b2->a_id,
    'Parent row access following unsetting parent row and setting parent by column');

$b3 = $bTable->createRow(array('bval' => 'b3'));
$a3 = $aTable->createRow(array('aval' => 'a3'));

$b3->A = $a3;
$b3->save();

assertTrue($a3->a_id !== null, 'Parent row was persisted by child row');
assertTrue($a3 === $b3->A,     'Child row retained reference to parent row after save');
assertTrue($a3->a_id == $b3->a_id, 'Child row has correct parent ID value');


$a4 = $aTable->createRow(array('aval' => 'a4'));
$a4->save();

$b4 = $bTable->createRow(array('bval' => 'b4'));
$b4->A = $a4;
$b4->save();

$a4->a_id = 128;
$a4->save(); // persist modified primary key in database

// What if refresh() is called instead of save()?
// new a_id value will be loaded from db,
// row corresponding to old a_id is in _parentRows and wont be detected upon
// access, so new a4 will be fetched.
// Conclusion: refresh() called explicitly may break connections between row
// objects.
$b4->save();
assertTrue($b4->a_id == 128,   'Parent row ID was updated');
assertTrue($b4->A === $a4,     'Parent row with modified primary key was retained');


$a5 = $aTable->createRow(array('aval' => 'a5'));
$b5 = $bTable->createRow(array('bval' => 'b5'));

$a5->B = $b5;
$b5->A = $a5;

$a5->save();

assertTrue($a5->isStored() && $b5->isStored(),  'Cyclic references are stored');
assertTrue($a5->B === $b5 && $b5->A === $a5,    'Cyclically referenced objects are retained');

// check if modified detached rows are not saved when referencing row is saved
$a6 = $aTable->createRow(array('aval' => 'a6'));
$b6 = $bTable->createRow(array('bval' => 'b6'));

$a6->B = $b6;
$a6->save();

$b6->bval = 'b.vi';

$a6->b_id = null;
$a6->save();

assertTrue($b6->isModified() === true, 'Detached rows are not saved');
assertTrue($a6->b_id === null,         'Detached rows are not referenced after save()');

// check if _postLoad is triggered whenever necessary
BTableRow::clearPostLoadLog();
$b7 = $bTable->createRow(array('bval' => 'b7'));
assertTrue(BTableRow::getPostLoadLog() === array(), 'Post-load logic is not triggered when row is not stored');

BTableRow::clearPostLoadLog();
$b7->save();
assertTrue(BTableRow::getPostLoadLog() === array(BTableRow::postLoadLogEntry($b7)), 'Post-load logic is triggered upon save()');

BTableRow::clearPostLoadLog();
$bTable->removeFromIdentityMap($b7);
$b8 = $bTable->findRow($b7->b_id);
assertTrue(BTableRow::getPostLoadLog() === array(BTableRow::postLoadLogEntry($b8)), 'Post-load logic is triggered on a stored row');


$db->closeConnection();
$db = null;
unlink($dbname);

function assertTrue($expr, $text) {
    if ($expr) {
        echo '  ', str_pad($text . ' ', 68, '.'), ' [  OK  ]', "\n";
    } else {
        echo '  ', str_pad($text . ' ', 68, '.'), " [ FAIL ]", "\n";
    }
    fflush(fopen('php://stdout', 'w'));
}
