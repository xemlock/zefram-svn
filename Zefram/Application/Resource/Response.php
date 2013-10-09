<?php

/**
 * Config options:
 *
 *   resources.response.class = "Zend_Controller_Response_Http"
 *   resources.response.headers.NAME = VALUE
 *   resources.response.headers.NAME.value = VALUE
 *   resources.response.headers.NAME.replace = no
 *   resources.response.httpResponseCode =
 *   resources.response.cookies.NAME = VALUE
 *   resources.response.cookies.NAME.value = VALUE
 *   resources.response.cookies.NAME.expire = 0
 *   resources.response.cookies.NAME.path = /
 *   resources.response.cookies.NAME.domain = NULL
 *   resources.response.cookies.NAME.secure = no
 *   resources.response.cookies.NAME.httpOnly = no
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

            if (isset($options['cookies']) && $this->_response->canSendHeaders(true)) {
                $this->_setCookies($options['cookies']);
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

    protected function _setCookies(array $cookies)
    {
        foreach ($cookies as $name => $cookie) {
            switch (true) {
                case is_scalar($cookie):
                    setcookie($name, $cookie, 0, '/');
                    break;

                case is_array($cookie) && isset($cookie['value']):
                    $value = $cookie['value'];

                    // by default expire cookie after end of current session
                    $expire = 0;

                    // use '/' instead of current directory path because of urls
                    // format used by ZF (i.e. controller/action/key/value)
                    $path = '/';

                    // use default values of remaining paramaters, see:
                    // http://php.net/manual/en/function.setcookie.php 
                    $domain = null;
                    $secure = false;
                    $httpOnly = false;

                    foreach ($cookie as $key => $val) {
                        switch (strtolower($key)) {
                            case 'expire':
                                // if 'expire' value is given it is treated as
                                // an offset in seconds from the current time
                                $expire = time() + $val;
                                break;

                            case 'path':
                                $path = strval($val);
                                break;

                            case 'domain':
                                $domain = strval($val);
                                break;

                            case 'secure':
                                $secure = (bool) $val;
                                break;

                            case 'httponly': // PHP 5.2.0
                                $httpOnly = (bool) $val;
                                break;
                        }
                    }

                    setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
                    break;

                default:
                    throw new Zend_Controller_Response_Exception("Invalid value for cookie '{$name}'");
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
