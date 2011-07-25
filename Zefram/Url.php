<?php

/**
 * Utility class for handling simplified URL specification.
 *
 * @package Zefram_Url
 * @author  xemlock
 * @version 2011-07-26
 */
abstract class Zefram_Url
{
    /**
     * Recognized schemes:
     * - module/controller/action
     * - controller/action
     * - controller
     * Parameters are separated by ?.
     */
    public static function unserialize($urlString)
    {
        $parts = explode('?', $urlString);            
        $path = explode('/', $parts[0]);
        
        $module     = 'default';
        $controller = 'index';
        $action     = 'index';

        if (count($path) > 2) {
            list($module, $controller, $action) = $path;
        } elseif (count($path) > 1) {
            list($controller, $action) = $path;
        } elseif (!empty($path[0])) {
            $controller = $path[0];
        }

        $opts = array('module' => null, 'controller' => null, 'action' => null);

        if (isset($parts[1])) {
            $params = explode('/', $parts[1]);
            for ($i = 0, $n = floor(count($params) / 2) * 2; $i < $n; $i += 2) {
                $key = $params[$i];
                $value = $params[$i + 1];
                $opts[$key] = $value;
            }
        }

        $opts['module']     = $module;
        $opts['controller'] = $controller;
        $opts['action']     = $action;
    
        return $opts;
    }
    
    public static function serialize(Zend_Controller_Request_Abstract $request)
    {
        $url = "{$request->module}/{$request->controller}/{$request->action}";
        $params = array();
        foreach ($request->getUserParams() as $key => $value) {
            if (in_array($key, array('module', 'controller', 'action'))) {
                continue;
            }
            $params[] = $key;
            $params[] = $value;
        }
        $params = count($params) ? '?' . implode('/', $params) : null;
        return $url . $params;
    }

    // @deprecated
    public static function fromRequest(Zend_Controller_Request_Abstract $request)
    {
        return self::serialize($request);
    }

    // @deprecated
    public static function toArray($urlString)
    {
        return self::unserialize($urlString);
    }
}
