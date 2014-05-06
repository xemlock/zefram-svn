<?php

class Zefram_Db_Table_Rowset extends Zend_Db_Table_Rowset
{
    protected function _loadAndReturnRow($position)
    {
        $row = parent::_loadAndReturnRow($position);
        $table = $this->getTable();

        if ($row && ($table instanceof Zefram_Db_Table)) {
            $table->addToIdentityMap($row);
        }

        return $row;
    }

    /**
     * Collect unique non-NULL values of given column from the rows in this rowset
     *
     * @param  string $columnName
     * @return array
     */
    public function collectColumn($columnName)
    {
        $fast = true;
        $values = array();

        foreach ($this as $row) {
            if (null !== ($value = $row->{$columnName})) {
                $fast = $fast && (is_int($value) || is_string($value));
                $values[] = $value;
            }
        }

        // When all non-NULL values were integers or strings use fast method
        // of extracting unique array values, see:
        // http://stackoverflow.com/questions/8321620/array-unique-vs-array-flip
        if ($fast) {
            return array_flip(array_flip($values));
        }

        return array_unique($values);
    }
}
