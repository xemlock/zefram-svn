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
        $this->addPrefixPath('Zefram_Form_Element_',   'Zefram/Form/Element/',   self::ELEMENT);
        $this->addPrefixPath('Zefram_Form_Decorator_', 'Zefram/Form/Decorator/', self::DECORATOR);

        $this->addElementPrefixPath('Zefram_Validate_', 'Zefram/Validate/', Zend_Form_Element::VALIDATE);
        $this->addElementPrefixPath('Zefram_Filter_',   'Zefram/Filter/', Zend_Form_Element::FILTER);

        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }
        $options = (array) $options;
        if (!isset($options['decorators'])) {
            $options['decorators'] = self::formDecorators();
        } elseif (!is_array($options['decorators'])) {
            $options['decorators'] = (array) $options['decorators'];
        }
        if (!isset($options['elementDecorators'])) {
            $options['elementDecorators'] = self::elementDecorators();
        }
        parent::__construct($options);
    }

    public function addSubForm(Zend_Form $form, $name = null, $order = null)
    {
        if ($name === null) {
            $name = $form->getName();
        }
        return parent::addSubForm($form, $name, $order);
    }

    /**
     * Adds the specified class(es) to the class attribute of this form
     * instance.
     *
     * @param string $className
     *     One or more class names to be added to the class attribute
     */
    public function addClass($className)
    {
        $class = implode(' ', array_keys(array_merge(
            array_flip(preg_split('/\s+/', $this->getAttrib('class'), 0, PREG_SPLIT_NO_EMPTY)),
            array_flip(preg_split('/\s+/', $className, 0, PREG_SPLIT_NO_EMPTY))
        )));
        if (strlen($class)) {
            $this->setAttrib('class', $class);
        } else {
            $this->removeAttrib('class');
        }
        return $this;
    }

    /**
     * Remove a single class, multiple classes, or all classes from class
     * attribute of this form instance.
     *
     * @param string $className
     *     OPTIONAL One or more space-separated classes to be removed from
     *     the class attribute
     */
    public function removeClass($className = null)
    {
        if (null === $className) {
            $this->removeAttrib('class');
        } else {
            $class = implode(' ', array_keys(array_diff_key(
                array_flip(preg_split('/\s+/', $this->getAttrib('class'), 0, PREG_SPLIT_NO_EMPTY)),
                array_flip(preg_split('/\s+/', $className, 0, PREG_SPLIT_NO_EMPTY))
            )));
            if (strlen($class)) {
                $this->setAttrib('class', $class);
            } else {
                $this->removeAttrib('class', $class);
            }
        }
        return $this;
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
            array_unshift($options['filters'],
                new Zefram_Filter_Scalar, // not scalars to empty strings
                'StringTrim',             // trim strings
                array('filter' => 'Null', 'options' => 'string') // convert empty strings to null
            );

            switch (strtolower($element)) {
                case 'button':
                case 'reset':
                case 'submit':
                    if (!isset($options['decorators'])) {
                        $options['decorators'] = self::buttonDecorators();
                    }
                    break;                    

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

                case 'file':
                    if (!isset($options['decorators'])) {
                        $options['decorators'] = self::fileDecorators();
                    }
                    break;

                case 'textarea':
                    // set textarea dimensions
                    if (!isset($options['cols'])) $options['cols'] = 50;
                    if (!isset($options['rows'])) $options['rows'] = 5;
                    break;
            }
            if (isset($options['decorators']) && !is_array($options['decorators'])) {
                $options['decorators'] = (array) $options['decorators'];
            }
        }

        parent::addElement($element, $name, $options);

        if (!$element instanceof Zend_Form_Element) {
            $element = $this->getElement($name);
            if (empty($element)) {
                throw new Exception('Element not found: ' . $name);
            }
        }
        if ($element->isArray()) {
            // if element expects an array value, remove filters that 
            // convert value to string
            $element->removeFilter('Zefram_Filter_Scalar')
                    ->removeFilter('StringTrim');
        }

        return $this;
    }

    static public function formDecorators() 
    {
        // <markupListStart>
        //   <markupListItemStart>
        //   <markupListItemEnd>
        // <markupListEnd>
        return array(
            new Zend_Form_Decorator_FormErrors(array(
                'ignoreSubForms'      => true,
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

    public static function buttonDecorators($opts = array())
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
                array('tag' => 'dl'),
            ),
        ), $opts);
    }

    public static function fileDecorators()
    {
        return array(
            'File',
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
                array('tag' => 'dl'),
            ),
        );
    }

    static public function hiddenDecorators() 
    {
        return array(
            'ViewHelper',
        );
    }

    protected static $_defaultPrefixPaths = array();

    /**
     * Set default plugin loaders for use with decorators and elements.
     *
     * @param  Zend_Loader_PluginLoader_Interface $loader
     * @param  string $type 'decorator' or 'element'
     * @throws Zend_Form_Exception on invalid type
     */
    public static function addDefaultPrefixPath($prefix, $path, $type)
    {
        $type = strtoupper($type);

        switch ($type) {
            case self::DECORATOR:
            case self::ELEMENT:
                self::$_defaultPrefixPaths[$type][$prefix] = $path;
                break;

            default:
                throw new Zend_Form_Exception(sprintf('Invalid type "%s" provided to addDefaultPrefixPath()', $type));
        }
    }

    public function getPluginLoader($type = null)
    {
        $type = strtoupper($type);

        if (!isset($this->_loaders[$type])) {
            $loader = parent::getPluginLoader($type);

            // add default prefix paths after creating loader
            if (isset(self::$_defaultPrefixPaths[$type])) {
                foreach (self::$_defaultPrefixPaths[$type] as $prefix => $path) {
                    $loader->addPrefixPath($prefix, $path);
                }
            }

            return $loader;
        }

        return $this->_loaders[$type];
    }
}
