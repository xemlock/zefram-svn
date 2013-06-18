<?php

class Zefram_Application_Resource_Session extends Zend_Application_Resource_Session
{
    protected $_sessionId;

    public function __construct($options = null)
    {
        if (isset($options['id'])) {
            $this->setId($options['id']);
            unset($options['id']);
        }

        parent::__construct($options);
    }

    public function setId($id)
    {
        $this->_sessionId = $id;
        return $this;
    }

    public function init()
    {
        if ($this->_sessionId) {
            Zend_Session::setId($this->_sessionId);
        }

        return parent::init();
    }
}
