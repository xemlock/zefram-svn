<?php

// FIXME Przenazwac do MultiException
class Zefram_Controller_Form_Exception extends Exception
{
    const DEFAULT_KEY = '__DEFAULT__';

    protected $_messages = array(self::DEFAULT_KEY => null);
    
    public function getMessages() 
    {
        return $this->_messages;
    }

    public function addMessages($messages, $key = self::DEFAULT_KEY)
    {
        if (isset($this->_messages[$key]) && !is_array($this->_messages[$key])) {
            $this->_messages[$key] = array($this->_messages[$key]);
        }
        $this->_messages[$key] = array_merge(
            isset($this->_messages[$key]) ? (array) $this->_messages[$key] : array(), 
            $messages
        );
    }

    public function setMessage($message, $key = self::DEFAULT_KEY)
    {
        // overwrites all messages writted previously to that key
        $this->_messages[$key] = (string) $message;
    }

    public function addMessage($message, $key = self::DEFAULT_KEY)
    {
        if (isset($this->_messages[$key])) {
            if (!is_array($this->_messages[$key])) {
                $this->_messages[$key] = array($this->_messages[$key]);
            }
        } else {
            $this->_messages[$key] = array();
        }
        $this->_messages[$key][] = (string) $message;
    }
}
