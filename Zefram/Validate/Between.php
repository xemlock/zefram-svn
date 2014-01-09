<?php

/**
 * Reimplementation of Between validator which can work as GreaterThanOrEqual
 * and LessThanOrEqual validators missing in ZF, and can be used as
 * a substitute for GreaterThan and LessThan validators as well.
 *
 * Shame on you, ZF!
 */
class Zefram_Validate_Between extends Zend_Validate_Between
{
    const NOT_BETWEEN          = 'notBetween';

    const NOT_GREATER          = 'notGreaterThan';

    const NOT_LESS             = 'notLessThan';

    const NOT_BETWEEN_STRICT   = 'notBetweenStrict';

    const NOT_GREATER_OR_EQUAL = 'notGreaterOrEqual';

    const NOT_LESS_OR_EQUAL    = 'notLessOrEqual';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::NOT_BETWEEN          => "'%value%' is not between '%min%' and '%max%', inclusively",
        self::NOT_BETWEEN_STRICT   => "'%value%' is not strictly between '%min%' and '%max%'",
        self::NOT_GREATER          => "'%value%' is not greater than '%min%'",
        self::NOT_GREATER_OR_EQUAL => "'%value%' is not greater than or equal to '%min%'",
        self::NOT_LESS             => "'%value%' is not less than '%max%'",
        self::NOT_LESS_OR_EQUAL    => "'%value%' is not less than or equal to '%max%'",
    );

    /**
     * Additional variables available for validation failure messages
     *
     * @var array
     */
    protected $_messageVariables = array(
        'min' => '_min',
        'max' => '_max',
    );

    /**
     * Minimum value
     *
     * @var mixed
     */
    protected $_min;

    /**
     * Maximum value
     *
     * @var mixed
     */
    protected $_max;

    /**
     * @var boolean
     */
    protected $_inclusive;

    /**
     * The following option keys are accepted:
     *   'min'       => scalar, minimum border
     *   'max'       => scalar, maximum border
     *   'inclusive' => boolean, inclusive border values
     *
     * @param  mixed $options
     * @return void
     */
    public function __construct($options = null)
    {
        if (is_object($options)) {
            if (method_exists($options, 'toArray')) {
                $options = $options->toArray();
            }
            $options = (array) $options;
        }

        if (!is_array($options)) {
            $options = func_get_args();
            $temp = array();
            if ($options) {
                $temp['min'] = array_shift($options);
            }
            if ($options) {
                $temp['max'] = array_shift($options);
            }
            if ($options) {
                $temp['inclusive'] = array_shift($options);
            }
            $options = $temp;
        }

        // if not explicitly forbidden comparisons are inclusive, to maintain
        // compatiility with original validator implementation
        if (!array_key_exists('inclusive', $options)) {
            $options['inclusive'] = true;
        }

        if (isset($options['min'])) {
            $this->setMin($options['min']);
        }

        if (isset($options['max'])) {
            $this->setMax($options['max']);
        }

        if (isset($options['inclusive'])) {
            $this->setInclusive($options['inclusive']);
        }
    }

    /**
     * Returns the min option
     *
     * @return mixed
     */
    public function getMin()
    {
        return $this->_min;
    }

    /**
     * Sets the min option
     *
     * @param  mixed $min
     * @return Zefram_Validate_Between
     */
    public function setMin($min)
    {
        $this->_min = $min;
        return $this;
    }

    /**
     * Returns the max option
     *
     * @return mixed
     */
    public function getMax()
    {
        return $this->_max;
    }

    /**
     * Sets the max option
     *
     * @param  mixed $max
     * @return Zefram_Validate_Between
     */
    public function setMax($max)
    {
        $this->_max = $max;
        return $this;
    }

    /**
     * Returns the inclusive option
     *
     * @return bool
     */
    public function getInclusive()
    {
        return $this->_inclusive;
    }

    /**
     * Sets the inclusive option
     *
     * @param  bool $inclusive
     * @return Zefram_Validate_Between
     */
    public function setInclusive($inclusive)
    {
        $this->_inclusive = $inclusive;
        return $this;
    }

    /**
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        if ($this->_inclusive) {
            if (isset($this->_min) && isset($this->_max)) {
                if ($value < $this->_min || $this->_max < $value) {
                    $this->_error(self::NOT_BETWEEN);
                    return false;
                }
            } elseif (isset($this->_min)) {
                if ($value < $this->_min) {
                    $this->_error(self::NOT_GREATER_OR_EQUAL);
                    return false;
                }
            } elseif (isset($this->_max)) {
                if ($this->_max < $value) {
                    $this->_error(self::NOT_LESS_OR_EQUAL);
                    return false;
                }
            }
        } else {
            if (isset($this->_min) && isset($this->_max)) {
                if ($value <= $this->_min || $this->_max <= $value) {
                    $this->_error(self::NOT_BETWEEN_STRICT);
                    return false;
                }
            } elseif (isset($this->_min)) {
                if ($value <= $this->_min) {
                    $this->_error(self::NOT_GREATER);
                    return false;
                }
            } elseif (isset($this->_max)) {
                if ($this->max <= $value) {
                    $this->_error(self::NOT_LESS);
                    return false;
                }
            }
        }
        return true;
    }

}
