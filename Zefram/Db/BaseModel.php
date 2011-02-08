<?php

abstract class ZUtils_Db_BaseModel extends Zend_Db_Table {
    protected $_id = 'id';
    protected $_ordering;
    protected $_rowClass = 'ZUtils_Db_BaseRow';
    
    public static function ConvertCamelCase($camelCase, $separator = '_') {
        $dashed = '';
        for ($i = 0; $i < strlen($camelCase); ++$i) {
            $c = $camelCase{$i};
            if ($i > 0 && ctype_upper($c)) $dashed .= $separator;
            $dashed .= $c;
        }
        return strtolower($dashed);
    }

    //protected function _setupTableName()
    public function init() {
        // table names are converted from camel-case to underscore-delimited lowercase
        $this->_name = self::ConvertCamelCase($this->_name);
    }
    
    public function fetchAll($where = null, $order = null, $count = null, $offset = null) {
        if ($order === null) $order = $this->_ordering;
        return parent::fetchAll($where, $order, $count, $offset);
    }

    public function fetchSelectOptions($where = null, $order = null, $count = null, $offset = null) {
        $opts = array();
        foreach ($this->fetchAll($where, $order, $count, $offset) as $row) {
            $opts[$row->id] = (String) $row;
        }
        return $opts;
    }
}

// vim: et sw=4 fdm=marker
