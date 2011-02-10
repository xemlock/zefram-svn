<?php

class Zefram_Controller_Form_Exception extends Exception
{
    protected $_messages = array();

    public function getMessages() 
    {
        return $this->_messages;
    }

    public function setMessages($messages)
    {
        $this->_messages = (array) $messages;
    }

    public function addMessage($message, $key = null)
    {
        if (null === key) {
            $this->_messages[] = $message;
        } else {
            $this->_messages[$key] = $message;
        }
    }
}
