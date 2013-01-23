<?php

interface Zefram_Db_Adapter_Interface
{
    public function getTable($name);
    public function escapePattern($string, $escapeChar);
}
