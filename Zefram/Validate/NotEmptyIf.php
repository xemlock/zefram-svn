<?php

/**
 * Conditional NotEmpty validator.
 *
 * If used on a Zend_Form_Element instance an allowEmpty flag must be set to
 * FALSE and required flag must be also set to FALSE (default).
 *
 * @version 2014-12-13 / 2014-02-25
 * @author xemlock
 */
class Zefram_Validate_NotEmptyIf extends Zend_Validate_NotEmpty
{
    /**
     * @var Zefram_Stdlib_CallbackHandler
     */
    protected $_callback;

    /**
     * @var string|null
     */
    protected $_contextKey;

    /**
     * Optional context value to match against
     * @var mixed|null
     */
    protected $_token;

    /**
     * Whether _contextCallback should be setup as a callback before performing
     * validation
     * @var bool
     */
    protected $_setupContextCallback;

    /**
     * @var bool
     */
    protected $_negate = false;

    /**
     * Constructor.
     *
     * Options:
     *     callback
     *     messages
     *
     * @param  array $options
     * @return void
     */
    public function __construct(array $options = null)
    {
        if (is_array($options)) {
            foreach ($options as $key => $value) {
                $method = 'set' . $key;
                if (method_exists($this, $method)) {
                    $this->{$method}($value);
                }
            }
        }
    }

    /**
     * As a side effect this function also resets callback.
     *
     * @param  string $key
     * @return Zefram_Validate_NotEmptyIf
     */
    public function setContextKey($key)
    {
        $this->_contextKey = (string) $key;
        $this->_setupContextCallback = true;
        return $this;
    }

    public function getContextKey()
    {
        return $this->_contextKey;
    }

    /**
     * Set token to validate against when matching context.
     *
     * @param  mixed $token
     * @return Zefram_Validate_NotEmptyIf
     */
    public function setToken($token)
    {
        $this->_token = $token;
        return $this;
    }

    /**
     * Retrieve token.
     *
     * @return mixed
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * @param  callable $callback
     * @return Zefram_Validate_NotEmptyIf
     * @throws Zend_Validate_Exception
     */
    public function setCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new Zend_Validate_Exception('Invalid callback given');
        }
        $this->_callback = $callback;
        $this->_setupContextCallback = false;
        return $this;
    }

    /**
     * @return callable
     */
    public function getCallback()
    {
        return $this->_callback;
    }

    /**
     * @param  bool $negate
     * @return Zefram_Validate_NotEmptyIf
     */
    public function setNegate($negate = true)
    {
        $this->_negate = (bool) $negate;
        return $this;
    }

    /**
     * @deprecated
     * @return bool
     */
    public function getNegate()
    {
        return $this->_negate;
    }

    /**
     * @param  mixed $value
     * @param  array $context
     * @return bool
     */
    public function isValid($value, $context = null)
    {
        if ($this->_setupContextCallback) {
            $this->_setupContextCallback = false;
            $this->setCallback(array($this, '_contextCallback'));
        }

        if ($this->_callback) {
            $test = call_user_func($this->_callback, $value, $context);
            $negate = $this->getNegate();

            if (($test && !$negate) || (!$test && $negate)) {
                return parent::isValid($value, $context);
            }

            return true;
        }

        // if no callback is given act as a standard NotEmpty validator
        return parent::isValid($value, $context);
    }

    /**
     * Internal function used as callback when context key is set.
     *
     * It returns TRUE only if key equal to _contextKey property is present
     * in context and, if _token value has been given, additionaly checks if
     * these values are equal.
     *
     * @param  mixed $value
     * @param  array $context
     * @return bool
     */
    public function _contextCallback($value, $context)
    {
        $token = isset($context[$this->_contextKey]) ? $context[$this->_contextKey] : null;
        return $token !== null && (
            // checks emptiness unless a value to match agains was provided
            ($this->_token === null && $token) || ($this->_token == $token)
        );
    }
}
