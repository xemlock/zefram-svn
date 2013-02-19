<?php

class Zefram_Controller_Action_Helper_RouteUrl extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Assembles a URL based on a given route.
     *
     * @param string $name  route name
     * @param array $params route parameters
     * @param bool $encode  urlencode parameter values
     * @return string
     */
    public function routeUrl($name, $params = array(), $reset = false, $encode = true)
    {

        $router = $this->getFrontController()->getRouter();
        return $router->assemble($params, $name, $reset, $encode);
    }

    /**
     * Proxies to {@see routeUrl()}.
     *
     * @return string
     */
    public function direct($name, $params = array(), $reset = false, $encode = true)
    {
        return $this->routeUrl($name, $params, $reset, $encode);
    }

    /**
     * Proxies to {@see routeUrl()}.
     *
     * @return string
     */
    public function __invoke($name, $params = array(), $reset = false, $encode = true)
    {
        return $this->routeUrl($name, $params, $reset, $encode);
    }
}
