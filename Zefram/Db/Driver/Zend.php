<?php

require_once 'Zend/Db/Table/Abstract.php';
require_once 'ZUtils/Db/Driver/Abstract.php';

/**
 * Driver for Zend_Db ORM
 */
class ZUtils_Db_Driver_Zend extends ZUtils_DB_Driver_Abstract
{
    protected $_model = null;

    public static function getDefaultConnection()
    {
        require_once 'Zend/Db/Table/Abstract.php';
        $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
        if (null === $adapter) {
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new Exception('No database adapter present');
        }
    }


    /**
     * Returns underlying table object specific to used driver.
     * @returns Zend_Db_Table object
     */
    public function getModel() 
    {
        if (!isset($this->_model)) {
            if (strpos($this->_modelName, '_') === false) {
                // Assume no PEAR-like naming
                require_once $this->_modelName . '.php';
            } else {
                require_once 'Zend/Loader.php';
                Zend_Loader::loadClass($this->_modelName);
            }
            $this->_model = new $this->_modelName;
        }
        return $this->_model;
    }

    public function getSpecs() 
    {
        $specs = $this->getModel()->info(Zend_Db_Table_Abstract::METADATA);
        return $specs;
    }

    public function getIdentifier() 
    {
        $primary = $this->getModel()->info(Zend_Db_Table_Abstract::PRIMARY);
        if (count($primary) == 1) {
          return reset($primary);
        }
        return array_values($primary);
    }

    public function getConnection() 
    {
        return $this->getModel()->getAdapter();
    }

    public function createRow() 
    {
        return $this->getModel()->createRow();
    }

    public function populateRecord($record, $data)
    {
        return $record->setFromArray($data);
    }

    public function find($id)
    {
        $row = null;
        try {
            $rowset = $this->getModel()->find($id);
            $row = $rowset->getRow(0);
        } catch (Exception $e) {}

        return $row ? $row : null;
    }

    /*
      Zend_Db relations are unidirectional, from child to parent (just like in 
      plain SQL definitions):

        Declare the $_referenceMap array in the class for each dependent table. 
        This is an associative array of reference "rules". A reference rule 
        identifies which table is the parent table in the relationship, and 
        also lists which columns in the dependent table reference which columns
        in the parent table.

      Therefore there is no requirement for special column names to handle 
      inheritance.

      Relation is given as an array with the following keys:
      - columns       => A string or an array of strings naming the foreign key
                         column names in the dependent table.
      - refTableClass => The class name of the parent table. Use the class name,
                         not the physical name of the SQL table.
      - refColumns    => A string or an array of strings naming the primary key
                         column names in the parent table. 
     */
    public function getParent($record = null) 
    {
        global $x;
        if (!isset($x)) $x = 0;
        $id = $x++;
        $log = false;

        $model = $this->getModel();
        $primary = $this->getIdentifier();
        foreach ($model->info(Zend_Db_Table_Abstract::REFERENCE_MAP) as $name => $rel) {
            $local = (array) $rel['columns'];
            $foreign = (array) $rel['refColumns'];

            if ($log) {
                echo "<fieldset><pre>" . $this->getName() . "\n";
                echo '  Local:   '; print_r($local); echo "\n";
                echo '  Foreign: '; print_r($foreign); echo "\n";
                echo "</fieldset>";
            }

            if (count($local) != count($foreign)) {
                if ($log) echo '#Local != #Foreign<br/>';
                continue;
            }
            if (count($local) > 1) {
                // composite keys are not supported
                if ($log) echo 'Composite keys are not supported';
                continue;
            }
            $local = (string) reset($rel['columns']);
            $foreign = (string) reset($rel['refColumns']);

            if ($local != $primary) {
                if ($log) printf("<pre style='color:red'>%d LOCAL:%s != PRIMARY:%s</pre>", $id, $local, $primary);
                continue;
            }

            $foreignModel = (string) $rel['refTableClass'];
            $foreignDriver = ZUtils_Db_Driver::get($foreignModel);

            // foreign end of the relation must be a primary key of a $foreignModel table
            // (yep, this can happen for example in MySQL or SQLite)
            if ($foreignDriver->getIdentifier() != $foreign) {
                if ($log) printf("<pre style='color:red'>%d FOREIGN_PK:%s != FOREIGN_FK:%s</pre>", $id, $foreignDriver->getIdentifier(), $foreign);
                break; // no more checks - foreign key doesn't point at a primary key
            }

            // local column of foreign key points to primary key column
            $parentRecord = null;
            if ($record) {
                try {
                   $parentRecord = $record->findParentRow($foreignModel);
                } catch (Exception $e) {}
            }  
            
            if ($log) printf("<pre style='color:green'>%d SUCCESS: %s.%s -> %s.%s</pre>", $id, $this->getName(), $local, $foreignModel, $foreign);
            return array(
                'model'  => $foreignModel,
                'record' => $parentRecord,
            );
        }

        return null;
    }
}
