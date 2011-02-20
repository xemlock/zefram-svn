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

    public function mangle($password, $salt = null)
    {
        if (null === $salt) {
            $salt = $this->salt();
        }
        $hash = Zend_Crypt::hash($this->_hashName, $salt . $password);
        return $salt . $this->_saltSeparator . $hash;
    }

    /**
     * Generates random string used as salt during mangling process.
     * If no length is given a random value between 4 and 16 will
     * be used.
     */
    public function salt($length = null)
    {
        $str = '';
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if (null === $length) {
            $length = mt_rand(4, 16);
        } else {
            $length = (int) $length;
        }

        for ($i = 0, $max = strlen($chars) - 1; $i < $length; ++$i) {
            $str .= $chars[mt_rand(0, $max)];
        }

        return $str;
    }
}
