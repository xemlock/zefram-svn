<?php

/**
 * @version 2013-05-02
 */
class Zefram_Controller_Action_Helper_AjaxResponse extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * @var string
     */
    protected $_ajaxResponseClass = 'Zefram_Controller_Action_AjaxResponse';

    /**
     * @var Zefram_Controller_Action_AjaxResponse_Abstract
     */
    protected $_ajaxResponse;

    public function setAjaxResponseClass($ajaxResponseClass)
    {
        $this->_ajaxResponseClass = (string) $ajaxResponseClass;
        return $this;
    }

    public function setAjaxResponse(Zefram_Controller_Action_AjaxResponse_Abstract $response)
    {
        $this->_ajaxResponse = $response;
        return $this;
    }

    public function getAjaxResponse()
    {
        if (null === $this->_ajaxResponse) {
            $ajaxResponseClass = $this->_ajaxResponseClass;
            $this->setAjaxResponse(new $ajaxResponseClass);
        }
        return $this->_ajaxResponse;
    }

    public function direct()
    {
        return $this->getAjaxResponse();
    }
}
