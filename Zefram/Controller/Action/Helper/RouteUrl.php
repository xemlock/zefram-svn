<?php

/**
 * @author xemlock
 * @version 2013-03-01
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
        $reset  = isset($options['reset'])  ? (bool) $options['reset']  : false;
        $encode = isset($options['encode']) ? (bool) $options['encode'] : true;

        // URI Template, RFC6570
        $template = isset($options['template']) ? $options['template'] : false;

        $router = Zend_Controller_Front::getInstance()->getRouter();
        $url = $router->assemble($params, $name, $reset, $encode);

        if ($template) {
            $url = str_ireplace(array('%7B', '%7D'), array('{', '}'), $url);
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
