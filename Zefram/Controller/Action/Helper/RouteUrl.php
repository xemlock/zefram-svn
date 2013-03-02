<?php

/**
 * @author xemlock
 * @version 2013-03-02
 */
class Zefram_Controller_Action_Helper_RouteUrl extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Assembles a URL based on a given route.
     *
     * @param string $name   route name
     * @param array $params  route parameters
     * @param array $options options
     * @return string
     */
    public function routeUrl($name, $params = array(), $options = null)
    {
        $reset    = isset($options['reset']) ? (bool) $options['reset'] : false;
        $encode   = isset($options['encode']) ? (bool) $options['encode'] : true;
        $template = isset($options['template']) ? (bool) $options['template'] : false;

        $router = Zend_Controller_Front::getInstance()->getRouter();
        $url = $router->assemble($params, $name, $reset, $encode);

        // URI Template, string interpolation based on RFC 6570
        // single-variable expressions only (no operators, no variable lists)
        if ($template) {
            $url = preg_replace('/%7B([_a-z0-9]+)%7D/i', '{\1}', $url);
        }

        return $url;
    }

    /**
     * Proxies to {@see routeUrl()}.
     *
     * @return string
     */
    public function direct($name, $params = array(), $options = null)
    {
        return $this->routeUrl($name, $params, $options);
    }

    /**
     * Proxies to {@see routeUrl()}.
     *
     * @return string
     */
    public function __invoke($name, $params = array(), $options = null)
    {
        return $this->routeUrl($name, $params, $options);
    }
}
