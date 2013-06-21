<?php

/**
 * @uses       Zend_Crypt
 * @uses       Zefram_Math
 * @category   Zefram
 * @package    Zefram_Auth
 * @copyright  Copyright (c) 2013 xemlock
 */
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
            // Generate random string used as salt during mangling process.
            // Salt length is given a random value between 4 and 16.
            $salt = Zefram_Math_Rand::getString(
                mt_rand(4, 16),
                '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
            );
        }
        $hash = Zend_Crypt::hash($this->_hashName, $salt . $password);
        return $salt . $this->_saltSeparator . $hash;
    }
}
