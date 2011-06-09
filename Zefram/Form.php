<?php

/**
 * Form enhancing Zend_Form.
 *
 * Enhancements:
 * - StringTrim filter present by default
 * - Null(string) filter present by default
 * - default element decorators - special decorators for hidden input
 * - TODO hidden input errors moved to general errors
 * - default form decorators -> show custom messages
 */
class Zefram_Form extends Zend_Form
{

    public function __construct($options = null)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }
        $options = (array) $options;
        if (!isset($options['decorators'])) {
            $options['decorators'] = self::formDecorators();
        }
        if (!isset($options['elementDecorators'])) {
            $options['elementDecorators'] = self::elementDecorators();
        }
        parent::__construct($options);
    }

    /**
     * Add a new element.
     *
     * Works exactly like @see Zend_Form::addElement, but handles specially
     * hidden, radio and textarea elements.
     * 'filters' can contain scalar value - it will be automatically converted
     * to array.
     * Elements have StringTrim and Null(string) filters by default.
     */
    public function addElement($element, $name = null, $options = null)
    {
        if (is_string($element)) {
            if ($options instanceof Zend_Config) {
                $options = $options->toArray();
            }
            $options = (array) $options;

            // add filters to the beginning of filters list
            if (!isset($options['filters'])) {
                $options['filters'] = array();
            } elseif (!is_array($options['filters'])) {
                $options['filters'] = (array) $options['filters'];
            }
            array_unshift($options['filters'], 'StringTrim');
            array_unshift($options['filters'], array('filter' => 'Null', 'options' => 'string'));

            switch (strtolower($element)) {
                case 'hidden':
                    // handle hidden field decorators
                    if (!isset($options['decorators'])) {
                        $options['decorators'] = self::hiddenDecorators();
                    }
                    break;

                case 'radio':
                    // load correct decorators for radio button (Bug ZF-10065)
                    if (!isset($options['decorators'])) {
                        $options['decorators'] = self::radioDecorators();
                    }
                    break;

                case 'textarea':
                    // set textarea dimensions
                    if (!isset($options['cols'])) $options['cols'] = 50;
                    if (!isset($options['rows'])) $options['rows'] = 5;
            }
            if (!isset($options['decorators'])) {
                $options['decorators'] = self::elementDecorators();
            } elseif (!is_array($options['decorators'])) {
                $options['decorators'] = (array) $options['decorators'];
            }
        }

        return parent::addElement($element, $name, $options);
    }

    static public function formDecorators() 
    {
        // <markupListStart>
        //   <markupListItemStart>
        //   <markupListItemEnd>
        // <markupListEnd>
        return array(
            new Zend_Form_Decorator_FormErrors(array(
                'onlyCustomFormErrors' => true,
                'markupListStart'     => '<div class="form-errors">',
                'markupListEnd'       => '</div>',
                'markupListItemStart' => '',
                'markupListItemEnd'   => '',
            )),
            'FormElements',
            array(
                'HtmlTag', 
                array('tag' => 'div', 'class' => 'form')
            ),
            'Form',
        );
    }

    public static function label($opts = array())
    {
        $opts['escape'] = false;
        $opts['requiredSuffix'] = '<span class="required">*</span>';
        return array(
            'Label',
            $opts,
        );
    }

    public static function elementDecorators($opts = array())
    {        
        return array_merge(array(
            'ViewHelper',
            array('Description', array(
                'tag' => 'p',
                'class' => 'hint',
                'escape' => false,
            )),
            'Errors',
            array( // field wrapped in <dd>
                array('data' => 'HtmlTag'),
                array('tag' => 'dd')
            ),
            self::label(array('tag' => 'dt')), // label wrapped in <dt>
            new Zefram_Form_Decorator_DlWrapper,
        ), $opts);
    }

    public static function radioDecorators($opts = array())
    {
        // Bug ZF-10065: Radio Element adds a Label decorator automatically
        return array_merge(array(
            'ViewHelper',
            array('Description', array(
                'tag' => 'p',
                'class' => 'hint',
                'escape' => false,
            )),
            'Errors',
            array( // field wrapped in <dd>
                array('data' => 'HtmlTag'),
                array('tag' => 'dd')
            ),
            self::label(array('tag' => 'dt', 'disableFor' => true)), // label wrapped in <dt>
            array( // row (label and element) wrapped in <dl>
                array('row' => 'HtmlTag'),
                array('tag' => 'dl')
            ),
        ), $opts);
    }

    static public function hiddenDecorators() 
    {
        return array(
            'ViewHelper',
        );
    }
}
