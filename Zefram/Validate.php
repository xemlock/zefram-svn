<?php

/**
 * Validator chain.
 *
 * Extension to Zend_Validate allowing validators to be passed the
 * same way as they are to {@see Zend_Form_Element::addValidator()}.
 *
 * Validator can be used in Zend_Form_Element in conjunction with
 * allowEmpty=FALSE, required=FALSE and NotEmptyIf validator.
 *
 * @uses      Zend_Validate
 * @uses      Zend_Loader
 * @author    xemlock
 * @version   2014-12-13 / 2013-10-18
 *
 * 2014-12-13: added allowEmpty feature
 */
class Zefram_Validate extends Zend_Validate
{
    /**
     * @var bool
     */
    protected $_allowEmpty = false;

    /**
     * @var bool
     */
    protected $_breakChainOnFailure = false;

    /**
     * @var Zend_Loader
     */
    protected $_pluginLoader;

    protected $_translator;

    /**
     * Constructor
     *
     * @param array|object $options
     */
    public function __construct($options = null)
    {
        if (null !== $options) {
            if (is_object($options) && method_exists($options, 'toArray')) {
                $options = $options->toArray();
            }

            $options = (array) $options;

            // set loader before loading any validators

            if (isset($options['pluginLoader'])) {
                $this->setPluginLoader($options['pluginLoader']);
                unset($options['pluginLoader']);
            }

            // add prefix paths before loading any validators

            if (isset($options['prefixPath'])) {
                $this->addPrefixPath($options['prefixPath']);
                unset($options['prefixPath']);
            }

            if (isset($options['prefixPaths'])) {
                $this->addPrefixPaths($options['prefixPaths']);
                unset($options['prefixPaths']);
            }

            foreach ($options as $key => $value) {
                $method = 'set' . $key;
                if (method_exists($this, $method)) {
                    $this->$method($value);
                    unset($options[$key]);
                }
            }

            if (isset($options['validators'])) {
                $this->addValidators($options['validators']);

            } elseif ($options) {
                // treat any remaining options as validators, if no explicit
                // 'validators' option is provided
                $this->addValidators($options);
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * Additionally, if 'allow empty' flag is set, an empty value automatically
     * passes validation.
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if ($this->_allowEmpty && ($value === '' || $value === null)) {
            $this->_messages = array();
            $this->_errors   = array();
            return true;
        }
        return parent::isValid($value);
    }

    /**
     * Set 'allow empty' flag
     *
     * When the 'allow empty' flag is enabled empty values will automatically
     * pass validation.
     *
     * @param  bool $flag
     */
    public function setAllowEmpty($flag)
    {
        $this->_allowEmpty = (bool) $flag;
        return $this;
    }

    /**
     * Get 'allow empty' flag
     *
     * @return bool
     */
    public function getAllowEmpty()
    {
        return $this->_allowEmpty;
    }

    /**
     * @param string|array|Zend_Validate_Interface $validator
     * @param bool $breakChainOnFailure
     * @param array $options
     */
    public function addValidator($validator, $breakChainOnFailure = null, array $options = null)
    {
        if (is_array($validator)) {
            $validator = array_slice($validator, 0, 3);
            $count = count($validator);

            switch (true) {
                case $count >= 3:
                    $options = (array) array_pop($validator);

                case $count >= 2:
                    $breakChainOnFailure = array_pop($validator);

                case $count >= 1:
                    $validator = array_pop($validator);
                    break;

                default:
                    throw new Zend_Validate_Exception(
                        'Validator specification if given as array, must be non-empty'
                    );
            }
        }

        if (null === $breakChainOnFailure) {
            $breakChainOnFailure = $this->_breakChainOnFailure;
        }

        if (isset($options['messages'])) {
            $messages = $options['messages'];
            unset($options['messages']);
        } else {
            $messages = null;
        }

        // TODO lazy validator loading
        if (!$validator instanceof Zend_Validate_Interface) {
            $className = $this->getPluginLoader()->load($validator);

            if (empty($options)) {
                $validator = new $className;
            } else {
                $ref = new ReflectionClass($className);
                if ($ref->hasMethod('__construct')) {
                    reset($options);
                    if (is_int(key($options))) {
                        $validator = $ref->newInstanceArgs($options);
                    } else {
                        $validator = $ref->newInstance($options);
                    }
                } else {
                    $validator = $ref->newInstance();
                }
            }
        }

        if ($messages) {
            if (is_array($messages)) {
                $validator->setMessages($messages);
            } elseif (is_string($messages)) {
                $validator->setMessage($messages);
            }
        }

        // do not index validators by class name, as it would prevent multiple
        // validators of the same class to co-exists
        $this->_validators[] = array(
            'instance' => $validator,
            'breakChainOnFailure' => (bool) $breakChainOnFailure,
        );
    }

    /**
     * @return array
     */
    public function getValidators()
    {
        $validators = array();
        foreach ($this->_validators as $validator) {
            $validators[] = $validator['instance'];
        }
        return $validators;
    }

    /**
     * @return Zefram_Validate
     */
    public function clearValidators()
    {
        $this->_validators = array();
        return $this;
    }

    /**
     * @param  array $validators
     * @return Zefram_Validate
     */
    public function addValidators(array $validators)
    {
        foreach ($validators as $spec) {
            $this->addValidator($spec);
        }
        return $this;
    }

    /**
     * @param  array $validators
     * @return Zefram_Validate
     */
    public function setValidators(array $validators)
    {
        $this->clearValidators();
        return $this->addValidators($validators);
    }

    /**
     * Remove a single validator by name
     *
     * @return Zefram_Validate
     */
    public function removeValidator($name)
    {
        $len = strlen($name);
        foreach ($this->_validators as $key => $validator) {
            $validatorClass = get_class($validator['instance']);
            if ($len > strlen($validatorClass)) {
                continue;
            }
            // substr_compare($haystack, $needle, $offset, $length, $case_insensitive)
            if (0 === substr_compare($validatorClass, $name, -$len, $len, true)) {
                unset($this->_validators[$key]);
                break;
            }
        }
        return $this;
    }

    /**
     * @param  bool $breakChainOnFailure
     * @return Zefram_Validate
     */
    public function setBreakChainOnFailure($breakChainOnFailure)
    {
        $this->_breakChainOnFailure = (bool) $breakChainOnFailure;
        return $this;
    }

    public function setTranslator($translator = null)
    {
        foreach ($this->_validators as $validator) {
            if (method_exists($validator, 'setTranslator')) {
                $validator->setTranslator($translator);
            }
        }
        $this->_translator = $translator;
        return $this;
    }

    public function getTranslator()
    {
        return $this->_translator;
    }

    /**
     * @return Zend_Loader_PluginLoader_Interface
     */
    public function getPluginLoader()
    {
        if (null === $this->_pluginLoader) {
            $this->_pluginLoader = new Zend_Loader_PluginLoader(array(
                'Zend_Validate_'   => 'Zend/Validate/',
                'Zefram_Validate_' => 'Zefram/Validate/',
            ));
        }
        return $this->_pluginLoader;
    }

    /**
     * @param  Zend_Loader_PluginLoader_Interface $loader
     * @return Zefram_Validate
     */
    public function setPluginLoader(Zend_Loader_PluginLoader_Interface $loader)
    {
        $this->_pluginLoader = $loader;
        return $this;
    }

    /**
     * @param  string $prefix
     * @param  string $path
     * @return Zefram_Validate
     */
    public function addPrefixPath($prefix, $path)
    {
        $this->getPluginLoader()->addPrefixPath($prefix, $path);
        return $this;
    }

    /**
     * @param  array $spec
     * @return Zefram_Validate
     */
    public function addPrefixPaths(array $spec)
    {
        foreach ($spec as $prefix => $path) {
            if (is_array($path)) {
                if (isset($path['prefix']) && isset($path['path'])) {
                    $this->addPrefixPath($path['prefix'], $path['path']);
                }
            } elseif (is_string($prefix)) {
                $this->addPrefixPath($prefix, $path);
            }
        }
        return $this;
    }

    /**
     * This allows method chaining after object instantitation.
     *
     * @param  array|Zend_Config $options
     * @return Zefram_Validate
     */
    public static function create($options = null)
    {
        return new self($options);
    }
}
