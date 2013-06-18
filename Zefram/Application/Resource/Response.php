<?php

/**
 * Config options:
 *
 *   resources.response.class = "Zend_Controller_Response_Http"
 *   resources.response.headers.NAME = VALUE
 *   resources.response.headers.NAME.value = VALUE
 *   resources.response.headers.NAME.replace = no
 *   resources.response.httpResponseCode =
 */
class Zefram_Application_Resource_Response extends Zend_Application_Resource_ResourceAbstract
{
    protected $_class = 'Zend_Controller_Response_Http';
    protected $_response;

    public function __construct($options = null)
    {
        if (isset($options['class'])) {
            $this->_class = $options['class'];
        }

        $responseClass = $this->_class;
        $this->_response = new $responseClass;

        parent::__construct($options);
    }

    /**
     * @param array $headers
     */
    public function setHeaders($headers)
    {
        foreach ((array) $headers as $name => $value) {
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
        return $this;
    }

    /**
     * @param int $code
     */
    public function setHttpResponseCode($code)
    {
        $this->_response->setHttpResponseCode($code);
        return $this;
    }

    public function init()
    {
        $bootstrap = $this->getBootstrap();
        $bootstrap->bootstrap('FrontController');

        $frontController = $bootstrap->getResource('FrontController');
        $frontController->setResponse($this->_response);

        return $this->_response;
    }
}
