<?php

/**
 * Usability enhancements for callback validator.
 */
class Zefram_Validate_Callback extends Zend_Validate_Callback
{
    protected $_exceptionMessage;
 
    public function isValid($value)
    {
        $this->_setValue($value);
 
        $options  = $this->getOptions();
        $callback = $this->getCallback();
        $args     = func_get_args();
        $options  = array_merge($args, $options);
 
        try {
            if (!call_user_func_array($callback, $options)) {
                $this->_error(self::INVALID_VALUE);
                return false;
            }
        } catch (Exception $e) {
            $this->_exceptionMessage = $e->getMessage();
            $this->_messageVariables['message'] = '_exceptionMessage';

            $this->_error(self::INVALID_CALLBACK);
            return false;
        }
 
        return true;
    }
}
