<?php

class Zefram_Controller_Plugin_ErrorHandler extends Zend_Controller_Plugin_Abstract
{
    const EXCEPTION_NO_CONTROLLER = 'EXCEPTION_NO_CONTROLLER';
    const EXCEPTION_NO_ACTION     = 'EXCEPTION_NO_ACTION';
    const EXCEPTION_NO_ROUTE      = 'EXCEPTION_NO_ROUTE';
    const EXCEPTION_PRIVILEGES    = 'EXCEPTION_PRIVILEGES';
    const EXCEPTION_OTHER         = 'EXCEPTION_OTHER';

    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        $this->_handleError($request, 'routeShutdown');
    }

    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->_handleError($request, 'postDispatch');
    }

    protected $_error = null;

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        if ($this->_error) {
            $request->setControllerName('error')
                    ->setActionName('error')
                    ->setParam('error_handler', $this->_error);
        }
    }

    protected function _handleError($request, $event)
    {
        $response = $this->getResponse();
        if ($response->isException()) {
            if ($this->_error) {
                // juz mamy blad, wiecej nam nie potrzeba
                return;
            }

            $exceptions = $response->getException();
            $exception  = $exceptions[0];

            $this->_error = self::createErrorHandler($exception, $request);
            $request->setDispatched(false); // wymus, zeby jeszcze raz wszedl do petli
        }
    }

    public static function createErrorHandler($exception, $request = null)
    {
        $error            = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
        $exceptionType    = get_class($exception);
        $error->exception = $exception;
        $error->request   = $request ? clone $request : null;

        switch ($exceptionType) {
            case 'Zend_Controller_Router_Exception':
                if (404 == $exception->getCode()) {
                    $error->type = self::EXCEPTION_NO_ROUTE;
                } else {
                    $error->type = self::EXCEPTION_OTHER;
                }
                break;

            case 'Zend_Controller_Dispatcher_Exception':
                $error->type = self::EXCEPTION_NO_CONTROLLER;
                break;

            case 'Zend_Controller_Action_Exception':
                if (404 == $exception->getCode()) {
                    $error->type = self::EXCEPTION_NO_ACTION;
                } else {
                    $error->type = self::EXCEPTION_OTHER;
                }
                break;

            case 'Zend_Acl_Exception':
                $error->type = self::EXCEPTION_PRIVILEGES;
                break;

            default:             
                $error->type = self::EXCEPTION_OTHER;
                break;
        }

        return $error;
    }
}

