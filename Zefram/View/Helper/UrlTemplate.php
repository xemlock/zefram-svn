<?php

/**
 * @version 2014-05-15
 */
class Zefram_View_Helper_UrlTemplate extends Zend_View_Helper_Abstract
{
    /**
     * Assembles an URL template based on a given route.
     *
     * @param  string $routeName         route name
     * @param  array $urlParams OPTIONAL optional URL parameters
     * @param  bool $reset OPTIONAL      whether to reset to the route defaults ignoring URL params
     * @param  bool $encode OPTIONAL     whether to encode URL parts on output
     * @return string
     */
    public function urlTemplate($routeName, array $urlParams = null, $reset = false, $encode = true)
    {
        $helper = Zend_Controller_Action_HelperBroker::getStaticHelper('urlTemplate');
        return $helper->urlTemplate($routeName, $urlParams, $reset, $encode);
    }
}
