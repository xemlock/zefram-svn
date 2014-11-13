<?php

/**
 * @version 2014-11-13
 */
class Zefram_Validate_ByteLength extends Zend_Validate_StringLength
{
    const INVALID   = 'byteLengthInvalid';
    const TOO_SHORT = 'byteLengthTooShort';
    const TOO_LONG  = 'byteLengthTooLong';

    /**
     * @var array
     */
    protected $_messageTemplates = array(
        self::INVALID   => "Invalid type given. String expected",
        self::TOO_SHORT => "Value is less than %min% bytes long",
        self::TOO_LONG  => "Value is more than %max% bytes long",
    );

    /**
     * @var array
     */
    protected $_messageVariables = array(
        'min' => '_min',
        'max' => '_max'
    );

    /**
     * Minimum length
     * @var int
     */
    protected $_min = 0;

    /**
     * Maximum length, no maximum length if null
     * @var int|null
     */
    protected $_max;

    /**
     * Sets validator options.
     *
     * @param  int|array $options
     * @return void
     */
    public function __construct($options = array())
    {
        if (is_object($options) && method_exists($options, 'toArray')) {
            $options = (array) $options->toArray();
        }

        if (!is_array($options)) {
            $options = func_get_args();

            $temp['min'] = array_shift($options);
            if (!empty($options)) {
                $temp['max'] = array_shift($options);
            }

            $options = $temp;
        }

        if (array_key_exists('min', $options)) {
            $this->setMin($options['min']);
        }

        if (array_key_exists('max', $options)) {
            $this->setMax($options['max']);
        }
    }

    /**
     * Returns true if the byte length of value is at least the min option and
     * no greater than the max option (when the max option is not null).
     *
     * @param  string $value
     * @return bool
     */
    public function isValid($value)
    {
        if (!is_string($value)) {
            $this->_error(self::INVALID);
            return false;
        }

        $this->_setValue($value);
        $length = strlen($value);

        if ($length < $this->_min) {
            $this->_error(self::TOO_SHORT);
        }

        if (null !== $this->_max && $this->_max < $length) {
            $this->_error(self::TOO_LONG);
        }

        return empty($this->_messages);
    }
}
