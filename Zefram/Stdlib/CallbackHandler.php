<?php

class Zefram_Stdlib_CallbackHandler extends Zend_Stdlib_CallbackHandler
{
    /**
     * @var array
     */
    protected $_args = array();

    /**
     * Constructor
     *
     * @param  callback $callback
     * @param  array $args OPTIONAL
     * @return void
     */
    public function __construct($callback, array $args = array())
    {
        // PHP versions prior to 5.2.2 cannot handle callbacks given
        // as class::method string
        if (version_compare(PHP_VERSION, '5.2.2', '<') &&
            is_string($callback) &&
            (false !== strpos($callback, '::'))
        ) {
            $callback = explode('::', $callback, 2);
        }

        // extract metadata to be passed to parent constructor
        // Metadata are distinguished from arguments by their key, a string
        // key indicates a metadatym whereas integer key an argument.
        $metadata = array();

        if ($args) {
            foreach ($args as $key => $value) {
                if (is_string($key)) { // metadatum
                    $metadata[$key] = $value;
                } else {
                    // numerical keys are left intact
                    unset($args[$key]);
                }
            }
        }

        parent::__construct($callback, $metadata);

        // push any arguments left
        foreach ($args as $arg) {
            $this->pushArg($arg);
        }
    }

    /**
     * Store argument to be used for callback invocation.
     *
     * @param  mixed $arg
     * @return Zefram_Stdlib_CallbackHandler
     */
    public function pushArg($arg)
    {
        $this->_args[] = $arg;
        return $this;
    }

    /**
     * Clear stored callback arguments.
     *
     * @return Zefram_Stdlib_CallbackHandler
     */
    public function clearArgs()
    {
        $this->_args = array();
        return $this;
    }

    /**
     * Invoke registered callback.
     *
     * @param  array $args OPTIONAL
     * @return mixed
     */
    public function call(array $args = array())
    {
        for ($i = count($this->_args) - 1; $i >= 0; --$i) {
            array_unshift($args, $this->_args[$i]);
        }
        return parent::call($args);
    }
}
