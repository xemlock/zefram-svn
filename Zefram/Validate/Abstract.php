<?php

/**
 * Abstract validator class with error message pluralization support.
 */
abstract class Zefram_Validate_Abstract extends Zend_Validate_Abstract
{
    /**
     * @param  string $messageKey
     * @param  string $value
     * @param  int $n
     *     value used for pluralization when translating the error message
     * @return string
     */
    protected function _error($messageKey, $value = null, $n = null)
    {
        // no numeric value given, fall back to default error handling
        if (null === $n) {
            return parent::_error($messageKey, $value);
        }

        if (!isset($this->_messageTemplates[$messageKey])) {
            return null;
        }

        if (null === $value) {
            $value = $this->_value;
        }

        $messageId = array($this->_messageTemplates[$messageKey], $n);

        if (($translator = $this->getTranslator())) {
            $message = $translator->translate($messageId);
        } else {
            $message = $messageId;
        }

        if (is_object($value)) {
            if (!in_array('__toString', get_class_methods($value))) {
                $value = get_class($value) . ' object';
            } else {
                $value = $value->__toString();
            }
        } else {
            $value = implode((array) $value);
        }

        if ($this->getObscureValue()) {
            $value = str_repeat('*', strlen($value));
        }

        $vars = array();
        foreach ($this->_messageVariables as $ident => $property) {
            $vars["%$ident%"] = implode(' ', (array) $this->$property);
        }
        $vars['%value%'] = $value;
        $message = strtr($message, $vars);

        $length = self::getMessageLength();
        if (($length > -1) && (strlen($message) > $length)) {
            $message = substr($message, 0, (self::getMessageLength() - 3)) . '...';
        }

        $this->_errors[] = $messageKey;
        $this->_messages[$messageKey] = $message;
    }
}
