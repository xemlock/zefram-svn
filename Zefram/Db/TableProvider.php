<?php

/**
 * Class for building DbTable instances.
 *
 * @category   Zefram
 * @package    Zefram_Db
 * @subpackage Table
 */
class Zefram_Db_TableProvider extends Zefram_Db_Table_Factory
{
    /**
     * @param  string $name
     * @return string
     */
    public function tableName($name)
    {
        if (strlen($this->_tablePrefix)) {
            $name = $this->_tablePrefix . $name;
        }
        return (string) $name;
    }
}
