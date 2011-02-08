<?php

require_once 'ZUtils/Db/Driver/Abstract.php';
require_once 'ZUtils/Db/Driver.php';

/**
 * Driver for Doctrine ORM 1.2
 */
class ZUtils_Db_Driver_Doctrine extends ZUtils_Db_Driver_Abstract
{
    protected $_model = null;

    public static function getDefaultConnection() 
    {
        // throws Doctrine_Connection_Exception if there is no open connection
        return Doctrine_Manager::getCurrentConnection();
    }

    /**
     * Returns underlying table object specific to used driver.
     * @returns Doctrine_Table object
     */
    public function getModel() 
    {
        if (!isset($this->_model)) {
            require_once 'Doctrine/Core.php';
            $this->_model = Doctrine_Core::getTable($this->_modelName);
        }
        return $this->_model;
    }

    public function getSpecs() 
    {
    /*
     * The value of each array element is an associative array
     * containing some of the following keys:
     * DATA_TYPE   => string; SQL datatype name of column
     * DEFAULT     => string; default expression of column, null if none
     * NULLABLE    => boolean; true if column can have nulls
     * LENGTH      => number; length of CHAR/VARCHAR
     * SCALE       => number; scale of NUMERIC/DECIMAL
     * PRECISION   => number; precision of NUMERIC/DECIMAL
     * UNSIGNED    => boolean; unsigned property of an integer type
     * PRIMARY     => boolean; true if column is part of the primary key
     * IDENTITY    => boolean; true if column is auto-generated with unique values
     */
        $columns = $this->getModel()->getColumns();
        foreach ($columns as $name => $column) {
            // FIXME NULLABLE: !notnull || (primary && autoincrement)
            $spec = array(
              'DATA_TYPE' => $column['type'],
              'LENGTH'    => $column['length'],
              'PRIMARY'   => isset($column['primary']) && $column['primary'],
              'NULLABLE'  => !(isset($column['notnull']) && $column['notnull']),
              'IDENTITY'  => isset($column['autoincrement']) && $column['autoincrement'],
            );
            foreach ($spec as $key => $value) {
                $columns[$name][$key] = $value;
            }            
        }
        return $columns;
    }

    public function getIdentifier()
    {
        return $this->getModel()->getIdentifier();
    }

    public function getConnection() 
    {
        return $this->getModel()->getConnection();
    }

    public function createRow() 
    {
        return new $this->_modelName;
    }

    public function find($id) 
    {
        return $this->getModel()->find($id);
    }

    public function populateRecord($record, $data)
    {
        return $record->fromArray($data);
    }

    /**
    Jak tego uzywac? Ano, po pierwsze trzeba wiedziec ze:
    1) Doctrine nie tworzy kluczy obcych dla kolumn, ktore sa jednoczesnie kluczami glownymi
    2) relacje w Doctrine sa dwukierunkowe i tak naprawde nie wiadomo, gdzie jest
       zrodlo relacji a gdzie jej koniec. Tzn. jezeli zdefiniujemy relacje typu 1-1 w jednej tabeli,
       w drugiej mamy automatycznie relacje 1 do wielu - wtedy jeden koniec relacji ma typ 1:1 wiec 
       sie zorientowac mozemy. Ale jezeli zdefiniujemy oba konce jako 1:1 (type:one), to wtedy kiszka.
    Wiec przyjalem zalozenia nastepujace (a'la Django):

        +--------------+           +---------+
        |      b       |           |    a    |
        +--------------+    FK     +---------+
        | PK: a_ptr_id |---------->| PK: id  |
        +--------------+           +---------+

        1) PK(b) REFERENCES PK(a)
        2) COLUMN_NAME(PK(b)) == UNDERSCORE_SEPARATED(CLASS_NAME(MODEL(a)))_ptr_id
        => PARENT_TABLE(b) == a
     */
    public function getParent($record = null) 
    {
                global $x;
                if (!isset($x)) $x = 1;
                $id = $x++;
                $log = false;

        $model = $this->getModel();
        $primary = $this->getIdentifier();
        // nie lecimy po relacjach ale po kolumnach -> jak sprawdzic czy kolumna jest fk!
        foreach ($model->getRelations() as $name => $rel) {
            $foreign = $rel->getForeign();
            $local = $rel->getLocal(); // column name of local end (with respect to $model table) of the relation

                if ($log) {
                        echo "<fieldset><pre>" . $this->getName() . "\n";
                        echo '  Local:   '; print_r($local); echo "\n";
                        echo '  Foreign: '; print_r($foreign); echo "\n";
                        echo "</fieldset>";
                }

            if (is_array($local) || is_array($foreign)) {
                // multicolumn PK are not supported
                continue;
            }

                if ($log) {
                        printf('<pre style="color:red">%d CURRENT:%s LOCAL:%s FOREIGN:%s</pre>', $id, $this->getName(), $local, $foreign);
                }

            // primary key of current table (modelName) must be also a foreign
            // key pointing to primary key of the other table (foreignModel)
            // => therefore this relation is of type 1-1
            // NOT IN DOCTRINE!!!
            if ($local != $primary) {
                if ($log) {
                        printf("<pre>%d LOCAL:%s != PRIMARY:%s</pre>", $id, $local, $primary);
                }
                continue;
            }

            $foreignModel = $rel->getClass();
            // expected local end column name: {target_class}_ptr_id
            $localExpected = ZUtils_Db_Driver::tableName($foreignModel) . '_ptr_id';

            if ($local != $localExpected) {
                if ($log) {
                        printf("<pre style='color:red'>%d LOCAL:%s != TABLE_NAME:%s</pre>", $id, $local, $localExpected);
                }
                break; // no more checks - the primary key's table name is incorrect
            }

            $foreignDriver = ZUtils_Db_Driver::get($foreignModel);

            // foreign end of the relation must be a primary key of a $foreignModel table
            // (yep, this can happen for example in MySQL or SQLite)
            if ($foreignDriver->getIdentifier() != $foreign) {
                if ($log) {
                        printf("<pre style='color:red'>%d FOREIGN_PK:%s != FOREIGN_FK:%s</pre>", $id, $foreignDriver->getIdentifier(), $foreign);
                }
                break; // no more checks - foreign key doesn't point at a primary key
            }

            $foreignProperty = $rel->getAlias();
            // local column of foreign key points to primary key column
            $parentRecord = null;
            if ($record) {
                try {
                   $parentRecord = $record->get($foreignProperty, true);
                } catch (Exception $e) {}
            }
                if ($log) {
                        printf("<pre style='color:green'>%d SUCCESS: %s.%s -> %s.%s</pre>", $id, $this->getName(), $local, $foreignModel, $foreign);
                }
            return array(
                'model'  => $foreignModel,
                'record' => $parentRecord,
            );
        }

        // no parent table nor parent row detected
        return null;
    }
}

