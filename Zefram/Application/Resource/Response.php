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
 *   resources.response.cookies.NAME.expires = 0
 *   resources.response.cookies.NAME.path = /
 *   resources.response.cookies.NAME.domain = NULL
 *   resources.response.cookies.NAME.secure = no
 *   resources.response.cookies.NAME.httpOnly = no
 *   resources.response.cookies.NAME.maxAge = NULL
 *   resources.response.cookies.NAME.version = NULL
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

            if (isset($options['cookies']) && is_array($options['cookies'])) {
                $this->_setResponseCookies($options['cookies']);
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

    protected function _setResponseCookies(array $cookies)
    {
        foreach ($cookies as $name => $spec) {
            switch (true) {
                case is_scalar($spec):
                    $spec = array('value' => $spec);
                    // intentional no break

                case is_array($spec) && isset($spec['value']):
                    $spec = array_merge(
                        array(
                            'name'     => $name,
                            'value'    => null,
                            'expires'  => 0,
                            // use '/' instead of current directory path because
                            // of urls format used by ZF (i.e. controller/action/key/value)
                            'path'     => '/',
                            'domain'   => null,
                            'secure'   => false,
                            'httponly' => false,
                            // max age and version are ignored when using setcookie
                            'maxage'   => null,
                            'version'  => null,
                        ),
                        array_change_key_case(
                            $spec,
                            CASE_LOWER
                        )
                    );

                    // if 'expires' value is not empty, treat it as an offset
                    // from the current time given in seconds
                    if ($spec['expires']) {
                        $spec['expires'] += time();
                    }

                    if (Zend_Version::compareVersion('1.12.0') <= 0) {
                        // http://framework.zend.com/manual/1.12/en/zend.controller.response.html#zend.controller.response.headers.setcookie
                        $cookie = new Zend_Http_Header_SetCookie();

                        foreach ($spec as $key => $value) {
                            $method = 'set' . $key;
                            if (method_exists($cookie, $method) && isset($value)) {
                                $cookie->$method($value);
                            }
                        }

                        $this->_response->setRawHeader($cookie);

                    } elseif ($this->_response->canSendHeaders(true)) {
                        // http://php.net/manual/en/function.setcookie.php
                        setcookie(
                            $spec['name'], $spec['value'], $spec['expires'], $spec['path'],
                            $spec['domain'], $spec['secure'], $spec['httponly']
                        );
                    }
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
