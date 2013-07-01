<?php

/**
 * @author xemlock
 * @version 2013-07-01
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
        if (isset($options['absolute'])) {
            $absolute = $options['absolute'];
            unset($options['absolute']);
        } else {
            $absolute = false;
        }

        $helper = Zend_Controller_Action_HelperBroker::getStaticHelper('routeUrl');
        $url = $helper->routeUrl($name, $urlOptions, $options);

        if ($absolute) {
            $url = $this->view->serverUrl() . $url;
        }

        return $url;
    }
}
