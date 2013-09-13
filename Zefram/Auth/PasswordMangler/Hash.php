<?php

/**
 * @uses       Zend_Crypt
 * @uses       Zefram_Math
 * @category   Zefram
 * @package    Zefram_Auth
 * @copyright  Copyright (c) 2013 xemlock
 * @version    2013-09-13
 */
class Zefram_Auth_PasswordMangler_Hash extends Zefram_Auth_PasswordMangler
{
    const ROUND_COUNT = 8192;

    protected $_hashName;
    protected $_saltSeparator = '.';
    protected $_rounds;

    /**
     * @param string $hashName
     * @param int $rounds
     */
    public function __construct($hashName, $rounds = self::ROUND_COUNT)
    {
        $this->_hashName = $hashName;

        if (!is_int($rounds) || $rounds <= 0) {
            throw new InvalidArgumentException('Round count must a positive integer');
        }
        $this->_rounds = $rounds;
    }

    public function validate($password, $challenge, $context = null)
    {
        if (substr($challenge, 0, 3) === '$M$') {
            // $M${rounds}${salt}${hash}
            $parts = explode('$', substr($challenge, 3), 3);
            if (count($parts) === 3) {
                list($rounds, $salt, ) = $parts;
                return $this->mangle($password, $salt, $rounds) == $challenge;
            } else {
                return false;
            }
        }

        // legacy code
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
     * @param  string|int $salt
     * @param  int $rounds
     * @return string
     */
    public function mangle($password, $salt = null, $rounds = null)
    {
        if (null === $salt) {
            // If salt is not given generate a (pseudo-)random string
            $salt = 16;
        }

        if (is_int($salt)) {
            // Generate random string used as salt during mangling process.
            if ($salt < 0) {
                throw new InvalidArgumentException('Salt length must be greater than 0');
            }
            $salt = Zefram_Math_Rand::getString($salt, Zefram_Math_Rand::ALNUM);
        }

        $salt = strval($salt);

        if (null === $rounds) {
            $rounds = $this->_rounds;
        }

        $rounds = intval($rounds);

        if ($rounds <= 0) {
            throw new InvalidArgumentException('Round count must a positive integer');
        }

        $header = '$M$' . $rounds;
        $hash = Zend_Crypt::hash($this->_hashName, $salt . $password);

        while (--$rounds > 0) {
            $hash = Zend_Crypt::hash($this->_hashName, $hash . $password);
        }

        return $header . '$' . $salt . '$' . $hash;
    }
}
