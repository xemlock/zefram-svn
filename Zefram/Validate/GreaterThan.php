<?php

/**
 * GreaterThan validator with added support for comparing against a value
 * from the context.
 *
 * @version 2014-02-21
 * @author xemlock
 */
class Zefram_Validate_GreaterThan extends Zend_Validate_GreaterThan
{
    const NO_CONTEXT_VALUE = 'noContextValue';

    protected $_min = 0;

    protected $_contextKey;

    protected $_messageTemplates = array(
        self::NOT_GREATER      => "'%value%' is not greater than '%min%'",
        self::NO_CONTEXT_VALUE => "No context value was provided to match against",
    );

    /**
     * @param   array|scalar $options
     * @reuturn void
     */
    public function __construct($options = null)
    {
        if (is_object($options) && method_exists($options, 'toArray')) {
            $options = $options->toArray();
        }

        if (is_array($options)) {
            if (isset($options['min'])) {
                $this->setMin($options['min']);
            }
            if (isset($options['contextKey'])) {
                $this->setContextKey($options['contextKey']);
            }
        }

        if (is_scalar($options)) {
            $this->setMin($options);
        }
    }

    /**
     * @param  int|string $contextKey
     * @return Zefram_Validate_GreaterThan
     */
    public function setContextKey($contextKey)
    {
        $this->_contextKey = $contextKey;
        return $this;
    }

    /**
     * @param  mixed $value
     * @param  array $context OPTIONAL
     * @return bool
     */
    public function isValid($value, $context = null)
    {
        $min = $this->getMin();

        if (isset($this->_contextKey)) {
            if (!isset($context[$this->_contextKey])) {
                $this->_error(self::NO_CONTEXT_VALUE);
                return false;
            }
            $this->setMin($context[$this->_contextKey]);
        }

        $valid = parent::isValid($value);
        $this->setMin($min);

        return $valid;
    }
}
