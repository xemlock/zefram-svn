<?php

/**
 * Config options:
 *
 *   resources.response.class = "Zend_Controller_Response_Http"
 *   resources.response.headers.NAME = VALUE
 *   resources.response.headers.NAME.value = VALUE
 *   resources.response.headers.NAME.replace = no
 *   resources.response.httpResponseCode =
 *
 * To create response with the default options:
 *
 *   resources.response = 1
 */
class Zefram_Application_Resource_Response extends Zend_Application_Resource_ResourceAbstract
{
    protected $_response;

    public function getResponse()
    {
        if (null === $this->_response) {
            $options = $this->getOptions();

            if (empty($options['class'])) {
                $class = 'Zend_Controller_Response_Http';
            } else {
                $class = $options['class'];
            }

            $this->_response = new $class;

            if (isset($options['headers']) && is_array($options['headers'])) {
                $this->_setResponseHeaders($options['headers']);
            }

            if (isset($options['httpResponseCode'])) {
                $this->_response->setHttpResponseCode($options['httpResponseCode']);
            }
        }

        return $this->_response;
    }

    protected function _setResponseHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            switch (true) {
                case is_string($value):
                    $this->_response->setHeader($name, $value);
                    break;

                case is_array($value) && isset($value['value']):
                    $replace = isset($value['replace']) && $value['replace'];
                    $this->_response->setHeader($name, $value['value'], $replace);
                    break;

                default:
                    throw new Zend_Controller_Response_Exception("Invalid value for header '{$name}'");
            }
        }
    }

    public function init()
    {
        $bootstrap = $this->getBootstrap();
        $bootstrap->bootstrap('FrontController');

        $response = $this->getResponse();

        $frontController = $bootstrap->getResource('FrontController');
        $frontController->setResponse($response);

        return $response;
    }
}
