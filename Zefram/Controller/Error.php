<?php

class Zefram_Controller_Error extends Zefram_Controller_Action
{
    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');

        if (!$errors) {
            $this->view->message = 'You have reached the error page';
            return;
        }

        switch ($errors->type) {
            case Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $this->view->message = 'Page not found';
                break;

            case Plugin_ErrorHandler::EXCEPTION_PRIVILEGES:
                $this->getResponse()->setHttpResponseCode(403);
                $this->view->message = 'Insufficient privileges to access this page';
                break;

            default:
                // application error
                // Avoiding classname lookups and fatal errors with instanceof in PHP 5.0,
                // according to: http://php.net/manual/en/language.operators.type.php
                $exception = $errors->exception;
                if ($exception instanceof Zefram_Exception) {
                    $this->getResponse()->setHttpResponseCode($exception->getCode());
                    $this->view->message = $exception->getMessage();
                } else {
                    $this->getResponse()->setHttpResponseCode(500);
                    $this->view->message = $this->getInvokeArg('displayExceptions') == true
                                         ? $exception->getMessage()
                                         : 'Application error';
                }
                break;
        }

        if ($this->_request->isXmlHttpRequest()) {
            // to avoid complications with AJAX response handling set code to 200 OK
            $this->getResponse()->setHttpResponseCode(200);
            $response = array(
                'status'  => 'error',
                'type'    => get_class($errors->exception),
                'message' => $this->view->message,
            );
            if ($this->getInvokeArg('displayExceptions') == true) {
                $response['data'] = $errors->exception->getTrace();
            }
            return $this->_helper->json($response);
        }

        // conditionally display exceptions
        if ($this->getInvokeArg('displayExceptions') == true) {
            $this->view->exception = $errors->exception;
        }

        
    }

    public function getLog()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap->hasResource('Log')) {
            return false;
        }
        $log = $bootstrap->getResource('Log');
        return $log;
    }


}

