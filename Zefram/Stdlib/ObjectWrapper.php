<?php

/**
 * This is a container for objects that allows additional custom variables
 * to be added to objects.
 *
 * @version 2014-07-05
 * @author xemlock
 */
class Zefram_Stdlib_ObjectWrapper implements ArrayAccess
{
    /**
     * @var object
     */
    protected $_object;

    /**
     * @var array
     */
    protected $_extras;

    /**
     * @param  object $object
     * @param  array|Traversable $extras
     */
    public function __construct($object, $extras = null)
    {
        $this->_object = (object) $object;

        if ($extras) {
            $this->addExtras($extras);
        }
    }

    /**
     * @param  array|Traversable $extras
     * @return Maniple_Model_ModelWrapper
     */
    public function addExtras($extras)
    {
        foreach ($extras as $key => $value) {
            $this->addExtra($key, $value);
        }
        return $this;
    }

    /**
     * @param  string $key
     * @param  mixed $value
     * @return Maniple_Model_ModelWrapper
     */
    public function addExtra($key, $value)
    {
        $this->_extras[(string) $key] = $value;
        return $this;
    }

    /**
     * Proxy to {@link addExtra()}.
     *
     * @param  string $key
     * @param  mixed $value
     */
    public function __set($key, $value)
    {
        return $this->addExtra($key, $value);
    }

    /**
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->_extras[$key])) {
            return $this->_extras[$key];
        }
        if (isset($this->_object->{$key})) {
            return $this->_object->{$key};
        }
        return null;
    }

    /**
     * @param  string $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->_extras[$key]) || isset($this->_object->{$key});
    }

    /**
     * @param  string $key
     * @return void
     */
    public function __unset($key)
    {
        if (isset($this->_extras[$key])) {
            unset($this->_extras[$key]);
        }
    }

    public function offsetGet($key)
    {
        return $this->__get($key);
    }

    public function offsetSet($key, $value)
    {
        return $this->__set($key, $value);
    }

    public function offsetUnset($key)
    {
        $this->__unset($key);
    }

    public function offsetExists($key)
    {
        return $this->__isset($key);
    }

    /**
     * Calls method on underlying object.
     *
     * @param  string $method
     * @param  array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->_object, $method), $args);
    }
}
