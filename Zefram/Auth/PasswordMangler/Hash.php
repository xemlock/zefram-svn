<?php

/**
 * @uses       Zend_Crypt
 * @uses       Zefram_Math
 * @category   Zefram
 * @package    Zefram_Auth
 * @copyright  Copyright (c) 2013 xemlock
 * @version    2013-07-01
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
            return Zend_Crypt::hash($this->_hashName, $password) == $challenge;
        }

        $salt = substr($challenge, 0, $pos);
        $hash = substr($challenge, $pos + 1);

        return Zend_Crypt::hash($this->_hashName, $salt . $password) == $hash;
    }

    /**
     * @param  string $password
     * @param  string $salt
     * @return string
     */
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
        if (strlen($salt)) {
            $hash = $salt . $this->_saltSeparator . $hash;
        }
        return $hash;
    }
}
