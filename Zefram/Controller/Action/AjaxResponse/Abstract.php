<?php

/**
 * @version 2013-05-02
 */
abstract class Zefram_Controller_Action_AjaxResponse_Abstract
{
    abstract public function setSuccess($message = null);

    abstract public function setData($data);

    abstract public function setError($message, $code = null);

    abstract public function getContentType();

    abstract public function getBody();

    public function send()
    {
        $response = Zend_Controller_Front::getInstance()->getResponse();
        $response->setBody($this->getBody());
        $response->setHeader('Content-Type', $this->getContentType());
        $response->sendHeaders();
        $response->sendResponse();
        return $this;
    }

    public function sendAndExit()
    {
        $this->send();
        if (class_exists('Zend_Session', false) && Zend_Session::isStarted()) {
            Zend_Session::writeClose();
        } elseif (isset($_SESSION)) {
            session_write_close();
        }
        exit;
    }
}
