<?php

class Zefram_Validate_ByteLength extends Zend_Validate_StringLength
{
    const INVALID   = 'byteLengthInvalid';
    const TOO_SHORT = 'byteLengthTooShort';
    const TOO_LONG  = 'byteLengthTooLong';

    protected $_messageTemplates = array(
        self::INVALID   => "Invalid type given. String expected",
        self::TOO_SHORT => "Value is less than %min% bytes long",
        self::TOO_LONG  => "Value is more than %max% bytes long",
    );

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
