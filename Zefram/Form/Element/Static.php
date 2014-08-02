<?php

/**
 * Element with unchangeable value
 *
 * @category    Zefram
 * @package     Zefram_Form
 * @uses        Zend_Form_Element
 */
class Zefram_Form_Element_Static extends Zend_Form_Element
{
    protected static $_counter = 0;

    /**
     * Constructor
     *
     * @param  string|array|Zend_Config $spec
     * @param  string|array|Zend_Config $options OPTIONAL
     *     this parameter is ignored if $spec is not a string. When given as
     *     a string it is used as value of this element.
     */
    public function __construct($spec, $options = null)
    {
        $value = null;

        if (!is_string($spec)) {
            if (is_object($spec) && method_exists($spec, 'toArray')) {
                $spec = $spec->toArray();
            }
            $options = (array) $spec;
            $spec = null;
        }

        if (is_string($options)) {
            $value = $options;
            $options = array();
        } else {
            $options = (array) $options;

            if (isset($options['value'])) {
                $value = $options['value'];
                unset($options['value']);
            } else {
                throw new InvalidArgumentException('value is required');
            }
        }

        if (is_string($spec)) {
            $options['name'] = $spec;
        }

        $this->_value = $value;

        parent::__construct($options);
    }

    /**
     * Do nothing
     *
     * @param  mixed $value
     * @return Zefram_Form_Element_Static
     */
    public function setValue($value)
    {
        return $this;
    }

    public function getValue()
    {
        return $this->_value;
    }
}
