<?php

/**
 * Verifies that a password matches a hash. This validator requires that
 * function password_verify() is available (it is implemented natively
 * since PHP 5.5.0).
 */
class Zefram_Validate_PasswordVerify extends Zend_Validate_Abstract
{
    const EMPTY_HASH = 'passwordVerifyEmptyHash';
    const INVALID    = 'passwordVerifyInvalid';

    /**
     * @var string
     */
    protected $_hash;

    protected $_messageTemplates = array(
        self::EMPTY_HASH => 'No hash was provided to match against',
        self::INVALID    => 'The password provided is invalid',
    );

    /**
     * Constructor.
     *
     * @param  string|array $options
     * @throws Zend_Validate_Exception
     */
    public function __construct($options = null)
    {
        if (!function_exists('password_verify')) {
            throw new Zend_Validate_Exception('Function password_verify() is not callable. Please upgrade to PHP 5.5 or install userland implementation. Read more at http://php.net/manual/en/function.password-verify.php');
        }
        if (is_string($options)) {
            $options = array('hash' => $options);
        }
        if (is_object($options) && method_exists($options, 'toArray')) {
            $options = $options->toArray();
        }
        if ($options) {
            $this->setOptions((array) $options);
        }
    }

    /**
     * @param  array $options
     * @return Zefram_Validate_PasswordVerify
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . $key;
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
        return $this;
    }

    /**
     * @param  string $hash
     * @return Zefram_Validate_PasswordVerify
     */
    public function setHash($hash)
    {
        $this->_hash = (string) $hash;
        return $this;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->_hash;
    }

    /**
     * @param  string $value
     * @return bool
     */
    public function isValid($value)
    {
        $value = (string) $value;
        $this->_setValue($value);

        $hash = $this->getHash();

        if (empty($hash)) {
            $this->_error(self::EMPTY_HASH);
            return false;
        }

        if (!password_verify($value, $hash)) {
            $this->_error(self::INVALID);
            return false;
        }

        return true;
    }
}
