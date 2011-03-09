<?php

// According to http://stackoverflow.com/questions/1086075/zend-framework-flashmessenger-problem
// there are PHP 5.2.x versions, that have a problem with 
// Zend_Controller_Action_Helper_FlashMessenger::addMessage() (line 143)
// self::$_session->{$this->_namespace}[] = $message;

class Zefram_Controller_Action_Helper_FlashMessenger extends Zend_Controller_Action_Helper_FlashMessenger
{
    public function addMessage($message)
    {
        $count = isset(self::$_session->{$this->_namespace}) 
               ? count(self::$_session->{$this->_namespace}) 
               : 0;
        parent::addMessage($message);
        if ($count == count(self::$_session->{$this->_namespace})) {
            $messages = self::$_session->{$this->_namespace};
            $messages[] = $message;
            self::$_session->{$this->_namespace} = $messages;
        }
        return $this;
    }
}
