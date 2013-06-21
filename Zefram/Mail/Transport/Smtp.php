<?php

class Zefram_Mail_Transport_Smtp extends Zend_Mail_Transport_Smtp
{
    public function __construct(array $config = array())
    {
        $host = isset($config['host']) ? $config['host'] : '127.0.0.1';
        parent::__construct($config);
    }
}
