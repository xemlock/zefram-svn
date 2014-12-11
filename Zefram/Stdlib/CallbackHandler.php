<?php

/**
 * CallbackHandler with preset arguments that will be passed to registered
 * callback upon its invocation. This allows a more functional style
 * programming.
 *
 * @package Zefram_Stdlib
 * @uses    Zend_Stdlib_CallbackHandler
 * @version 2013-12-10
 * @author  xemlock
 *
 * ChangeLog:
 *
 * 2014-12-11  Xemlock
 *
 * * [new] added invoke() method
 */
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
        // copy constructor, use internal callback value
        if ($callback instanceof Zend_Stdlib_CallbackHandler) {
            $callback = $callback->getCallback();
        }

        // PHP versions prior to 5.2.2 cannot handle callbacks given
        // as class::method string
        if (version_compare(PHP_VERSION, '5.2.2', '<') &&
            is_string($callback) &&
            (false !== strpos($callback, '::'))
        ) {
            $callback = explode('::', $callback, 2);
        }

        // Since PHP 5.3.0 objects are callable if __invoke() method is
        // implemented.
        // Use __invoke method when it is detected and given callback object is
        // not callable (PHP 5.0.0 - 5.2.x)
        if (is_object($callback) && !is_callable($callback) &&
            method_exists($callback, '__invoke')
        ) {
            $callback = array($callback, '__invoke');
        }

        // extract metadata to be passed to parent constructor
        // Metadata are distinguished from arguments by their key, a string
        // key indicates a metadatum whereas integer key an argument.
        $metadata = array();

        if ($args) {
            foreach ($args as $key => $value) {
                if (is_string($key)) {
                    // string key indicates a metadatum, move it to $metadata
                    // array, numerical keys in $data are left intact
                    $metadata[$key] = $value;
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

    /**
     * Invoke registered callback with explicitly listed arguments.
     *
     * @param  mixed $param OPTIONAL
     * @param  mixed $param,... OPTIONAL
     * @return mixed
     */
    public function invoke($param = null)
    {
        // arguments not provided won't be included, from PHP doc:
        // func_get_args() returns a copy of the passed arguments only, and
        // does not account for default (non-passed) arguments.
        // http://php.net/manual/en/function.func-get-args.php
        $args = func_get_args();
        return $this->call($args);
    }
}
