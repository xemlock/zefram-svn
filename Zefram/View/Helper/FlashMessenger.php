<?php

class Zefram_View_Helper_FlashMessenger extends Zend_View_Helper_Abstract
{
    protected $_flashMessenger;

    protected function _getFlashMessenger()
    {
        if (null === $this->_flashMessenger) {
            $this->_flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('flashMessenger');
        }
        return $this->_flashMessenger;
    }

    public function getMessage($namespace = null)
    {
        foreach ($this->_getFlashMessenger()->getMessages($namespace) as $message) {
            return $message;
        }
    }

    public function getMessages($namespace = null)
    {
        return $this->_getFlashMessenger()->getMessages($namespace);
    }

    public function hasMessages($namespace = null)
    {
        return $this->_getFlashMessenger()->hasMessages($namespace);
    }

    public function flashMessenger()
    {
        return $this;
    }

    public function __toString()
    {
        return (string) $this->getMessage();
    }
}
