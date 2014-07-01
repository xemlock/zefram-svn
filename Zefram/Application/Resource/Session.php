<?php

class Zefram_Application_Resource_Session extends Zend_Application_Resource_Session
{
    protected $_sessionId;

    protected $_start;

    public function __construct($options = null)
    {
        if (isset($options['id'])) {
            $this->setId($options['id']);
            unset($options['id']);
        }

        if (isset($options['start'])) {
            $this->setStart($options['start']);
            unset($options['start']);
        }

        parent::__construct($options);
    }

    public function setId($id)
    {
        $this->_sessionId = $id;
        return $this;
    }

    public function setStart($flag)
    {
        $this->_start = (bool) $flag;
        return $this;
    }

    public function init()
    {
        if ($this->_sessionId) {
            Zend_Session::setId($this->_sessionId);
        }

        parent::init();

        if ($this->_start) {
            Zend_Session::start();
        }
    }
}
