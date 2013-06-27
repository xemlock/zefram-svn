<?php

// According to http://stackoverflow.com/questions/1086075/zend-framework-flashmessenger-problem
// there are PHP 5.2.x versions, that have a problem with 
// Zend_Controller_Action_Helper_FlashMessenger::addMessage() (line 143):
// self::$_session->{$this->_namespace}[] = $message;

class Zefram_Controller_Action_Helper_FlashMessenger extends Zend_Controller_Action_Helper_FlashMessenger
{
    public function addMessage($message, $namespace = null)
    {
        if (!is_string($namespace) || $namespace == '') {
            $namespace = $this->getNamespace();
        }

        $count = isset(self::$_session->{$namespace}) 
               ? count(self::$_session->{$namespace}) 
               : 0;

        // line 143 issues the following notice:
        //   Notice: Indirect modification of overloaded property 
        //   Zend_Session_Namespace::$default has no effect in 
        //   Zend\Controller\Action\Helper\FlashMessenger.php on line 143
        @parent::addMessage($message, $namespace);

        if ($count == count(self::$_session->{$namespace})) {
            $messages = self::$_session->{$namespace};
            $messages[] = $message;
            self::$_session->{$namespace} = $messages;
        }

        return $this;
    }
}
