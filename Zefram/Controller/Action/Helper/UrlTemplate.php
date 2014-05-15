<?php

/**
 * @varsion 2014-05-15
 */
class Zefram_Controller_Action_Helper_UrlTemplate extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Assembles an URL template based on a given route.
     *
     * @param  string $routeName         route name
     * @param  array $urlParams OPTIONAL optional URL parameters
     * @param  bool $reset OPTIONAL      whether to reset to the route defaults ignoring URL params
     * @param  bool $encode OPTIONAL     whether to encode URL parts on output
     * @return string
     * @throws Zend_Controller_Router_Exception
     */
    public function urlTemplate($routeName, array $urlParams = null, $reset = false, $encode = true)
    {
        $router = $this->getFrontController()->getRouter();
        $route = $router->getRoute($routeName);

        $urlParams = (array) $urlParams;

        // encode URL params if needed, leave NULL values intact
        if ($encode) {
            foreach ($urlParams as $key => $value) {
                if ($value === null) {
                    continue;
                }
                $urlParams[$key] = urlencode($value);
            }
        }

        // build parameters, replace absent URL params with placeholders
        // in ZF parameter notation (:param)
        if (method_exists($route, 'getVariables')) {
            // Zend_Controller_Router_Route
            // Zend_Controller_Router_Route_Hostname
            // Zend_Controller_Router_Route_Regex
            foreach ($route->getVariables() as $var) {
                if (!isset($urlParams[$var])) {
                    $urlParams[$var] = ':' . $var;
                }
            }
        }

        return $router->assemble($urlParams, $routeName, $reset, false);
    }

    /**
     * Proxies to {@see urlTemplate()}.
     *
     * @return string
     */
    public function direct($routeName, array $urlParams = null, $reset = null, $encode = null)
    {
        // performance tweak - direct method calls depending of arguments
        // passed rather than a single generic call to call_user_func_array()
        switch (func_num_args()) {
            case 4:
                return $this->urlTemplate($routeName, $urlParams, $reset, $encode);

            case 3:
                return $this->urlTemplate($routeName, $urlParams, $reset);

            case 2:
                return $this->urlTemplate($routeName, $urlParams);

            default:
                return $this->urlTemplate($routeName);
        }
    }
}
