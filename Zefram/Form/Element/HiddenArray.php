<?php

class Zefram_Form_Element_HiddenArray extends Zend_Form_Element_Hidden
{
    /**
     * Default view helper to use
     * @var string
     */
    public $helper = 'formHiddenArray';

    /**
     * @var bool
     */
    protected $_isArray = true;

    /**
     * Element decorators
     * @var array
     */
    protected $_decorators = array('ViewHelper');

    /**
     * @return array
     */
    public function getValue()
    {
        $valueFiltered = (array) $this->_value;
        array_walk_recursive($valueFiltered, array($this, '_filterValue'));
        return $valueFiltered;
    }

    /**
     * @return array
     */
    public function getUnfilteredValue()
    {
        return (array) $this->_value;
    }

    /**
     * @var array $value
     */
    public function setValue($value)
    {
        $this->_value = (array) $value;
        return $this;
    }

    /**
     * Although this element represents an array, all array values are
     * treated as a single value. Therefore error messages apply to all
     * array of values, not to a single value.
     *
     * @return array
     */
    protected function _getErrorMessages()
    {
        $translator = $this->getTranslator();
        $messages = $this->getErrorMessages();
        $value = null;

        foreach ($messages as $key => $message) {
            if (null !== $translator) {
                $message = $translator->translate($message);
            }
            if (false !== strpos($message, '%value%')) {
                if (null === $value) {
                    $value = implode(', ', $this->getValue());
                }
                $message = str_replace('%value%', $value, $message);
            }
            $messages[$key] = $message;
        }
        return $messages;
    }
}
