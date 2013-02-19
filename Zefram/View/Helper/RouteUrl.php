<?php

/**
 * @author xemlock
 * @version 2013-02-19
 */
class Zefram_View_Helper_RouteUrl extends Zend_View_Helper_Abstract
{
    /**
     * @params string $route
     * @params array $urlOptions
     * @return string
     */
    public function routeUrl($route, array $urlOptions = array(), $reset = false, $encode = true)
    {
        $router = Zend_Controller_Front::getInstance()->getRouter();
        return $router->assemble($urlOptions, $name, $reset, $encode);
    }
}
