<?php

class Zefram_Validate_NotEqual extends Zend_Validate_Abstract
{
    const IS_EQUAL = 'isEqual';
    const NO_TOKEN = 'noToken';

    /**
     * @var bool
     */
    protected $_useContext = false;

    /**
     * @var mixed
     */
    protected $_token;

    /**
     * @var string
     */
    protected $_tokenString;

    protected $_messageTemplates = array(
        self::IS_EQUAL => "Value cannot equal '%token%'",
        self::NO_TOKEN => "No token was provided to match against",
    );

    protected $_messageVariables = array(
        'token' => '_tokenString',
    );

    /**
     * Constructor.
     *
     * @param  array $options
     */
    public function __construct($options = null)
    {
        if (is_object($options) && method_exists($options, 'toArray')) {
            $options = $options->toArray();

        } elseif (is_scalar($options)) {
            $options = array('token' => $options);
        }

        foreach ((array) $options as $key => $value) {
            $method = 'set' . $key;
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    /**
     * @param  bool $useContext
     * @return Zefram_Validate_NotEqual
     */
    public function setUseContext($useContext)
    {
        $this->_useContext = (bool) $useContext;
        return $this;
    }

    /**
     * @return bool
     */
    public function getUseContext()
    {
        return $this->_useContext;
    }

    /**
     * @param  mixed $token
     * @return Zefram_Validate_NotEqual
     */
    public function setToken($token)
    {
        $this->_token = $token;
        $this->_tokenString = (string) $token;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value, $context = null)
    {
        $this->_setValue($value);

        $token = $this->getToken();

        if ($this->getUseContext()) {
            $token = isset($context[$token]) ? $context[$token] : null;
        }

        if ($token === null) {
            $this->_error(self::NO_TOKEN);
            return false;
        }

        if ($value == $token) {
            $this->_error(self::IS_EQUAL);
            return false;
        }

        return true;
    }    
}
