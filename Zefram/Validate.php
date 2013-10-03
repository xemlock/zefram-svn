<?php

/**
 * Extension to Zend_Validate allowing validators to be passed the
 * same way as they are to {@see Zend_Form_Element::addValidator()}.
 *
 * @uses      Zend_Validate
 * @uses      Zend_Loader
 * @author    xemlock
 * @version   2013-10-03
 */
class Zefram_Validate extends Zend_Validate
{
    protected $_pluginLoader;

    protected $_breakChainOnFailure = false;

    protected $_translator;

    /**
     * @param array|Zend_Config $options
     */
    public function __construct($options = null)
    {
        if (null !== $options) {
            if (is_object($options) && method_exists($options, 'toArray')) {
                $options = $options->toArray();
            }

            $options = (array) $options;

            // add prefix paths before loading any validators

            if (isset($options['prefixPath'])) {
                $this->addPrefixPath($options['prefixPath']);
            }

            if (isset($options['prefixPaths'])) {
                $this->addPrefixPaths($options['prefixPaths']);
            }

            foreach ($options as $key => $value) {
                $method = 'set' . $key;
                if (method_exists($this, $method)) {
                    $this->$method($value);
                }
            }
        }
    }

    /**
     * @param string|Zend_Validate_Interface $validator
     * @param bool $breakChainOnFailure
     * @param array $options
     */
    public function addValidator($validator, $breakChainOnFailure = null, array $options = null)
    {
        if (null === $breakChainOnFailure) {
            $breakChainOnFailure = $this->_breakChainOnFailure;
        }

        if (isset($options['messages'])) {
            $messages = $options['messages'];
            unset($options['messages']);
        } else {
            $messages = null;
        }

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
            if (is_array($spec)) {
                $breakChainOnFailure = null;
                $options = array();
                $count = count($spec);

                switch (true) {
                    case 0 == $count:
                        break;

                    case 1 <= $count:
                        $validator = array_shift($spec);

                    case 2 <= $count:
                        $breakChainOnFailure = array_shift($spec);

                    case 3 <= $count:
                        $options = array_shift($spec);

                    default:
                        $this->addValidator($validator, $breakChainOnFailure, $options);
                }
            } else {
                $this->addValidator($spec);
            }
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
     * @return Zend_Loader_PluginLoader
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
