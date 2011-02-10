<?php

require_once 'Zend/Crypt.php';

class Zefram_Auth_PasswordMangler_Hash extends Zefram_Auth_PasswordMangler
{
    protected $_hashName;
    protected $_saltSeparator = '.';

    public function __construct($hashName)
    {
        $this->_hashName = $hashName;
    }

    public function validate($password, $challenge, $context = null)
    {
        $pos = strpos($challenge, $this->_saltSeparator);
        if ($pos === false) {
            return false;
        }
        $salt = substr($challenge, 0, $pos);
        $hash = substr($challenge, $pos + 1);        
        return Zend_Crypt::hash($this->_hashName, $salt . $password) == $hash;
    }

    public function mangle($password)
    {
        $salt = $this->salt();
        $hash = Zend_Crypt::hash($this->_hashName, $salt . $password);
        return $salt . $this->_saltSeparator . $hash;
    }

    public function salt()
    {
        $str = '';
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $length = mt_rand(4, 16);

        for ($i = 0, $len = strlen($chars); $i < $length; ++$i) {
            $str .= $chars[mt_rand(0, $len)];
        }

        return $str;
    }
}
