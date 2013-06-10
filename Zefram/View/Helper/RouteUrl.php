<?php

/**
 * @author xemlock
 * @version 2013-06-10
 */
class Zefram_View_Helper_RouteUrl extends Zend_View_Helper_Abstract
{
    /**
     * Assembles a URL based on a given route.
     *
     * @param string $route
     * @param array $urlOptions
     * @param array $options
     * @return string
     */
    public function routeUrl($name, $urlOptions = null, $options = null)
    {
        $helper = Zend_Controller_Action_HelperBroker::getStaticHelper('routeUrl');
        return $helper->routeUrl($name, $urlOptions, $options);
    }
}
