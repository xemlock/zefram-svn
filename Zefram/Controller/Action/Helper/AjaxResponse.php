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
     * @param string $ajaxResponseClass
     */
    public function setAjaxResponseClass($ajaxResponseClass)
    {
        $this->_ajaxResponseClass = (string) $ajaxResponseClass;
        return $this;
    }

    /**
     * @return Zefram_Controller_Action_AjaxResponse_Abstract
     * @throws Zefram_Controller_Action_Exception_InvalidArgument
     */
    public function createAjaxResponse()
    {
        $ajaxResponseClass = $this->_ajaxResponseClass;
        $ajaxResponse = new $ajaxResponseClass;
        if (!$ajaxResponse instanceof Zefram_Controller_Action_AjaxResponse_Abstract) {
            throw new Zefram_Controller_Action_Exception_InvalidArgument(
                "AjaxResponse must be an instance of Zefram_Controller_Action_AjaxResponse_Abstract"
            );
        }
        return $ajaxResponse;
    }

    public function direct()
    {
        return $this->createAjaxResponse();
    }
}
