<?php

/**
 * Conditional NotEmpty validator.
 *
 * @version 2014-02-25
 * @author xemlock
 */
class Zefram_Validate_NotEmptyIf extends Zend_Validate_NotEmpty
{
    /**
     * @var callable
     */
    protected $_callback;

    /**
     * @var bool
     */
    protected $_negate = false;

    /**
     * Options:
     *     type
     *     callback
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
     * @param  callback $callback
     * @return Zefram_Validate_NotEmptyIf
     */
    public function setCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new Zend_Validate_Exception('Invalid callback given');
        }
        $this->_callback = $callback;
        return $this;
    }

    /**
     * @return callback|null
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
        $callback = $this->getCallback();

        if ($callback) {
            $test = call_user_func($callback, $value, $context);
            $negate = $this->getNegate();

            if (($test && !$negate) || (!$test && $negate)) {
                return parent::isValid($value, $context);
            }

            return true;
        }

        // if no callback is given act as a standard NotEmpty validator
        return parent::isValid($value, $context);
    }
}
