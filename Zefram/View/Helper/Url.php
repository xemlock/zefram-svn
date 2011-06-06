<?php

/*
_getPlugin
  getPluginLoader(type=_helper)->load(name=url)
    jezeli nie istnieje typ w _loaders, to zaladuj
    namespace Zend_View_ (Zend/View/)

Zend_View
  addHelperPath(path, prefix)
    _addPluginPath('helper', path, prefix)
      getPluginLoader('helper')->addPrefixPath(prefix, path)

*/

/**
 * Enhancement for Zend_View_Helper_Url aimed at simplifying passing
 * of url options (array notation is inconvenient as hell).
 */
class Zefram_View_Helper_Url extends Zend_View_Helper_Url
{
    protected static $_trans_sid = false;

    public static function enableTransSid($enable = true)
    {
        self::$_trans_sid = (bool) $enable;
    }

    /**
     * Warning: when passing path as string and route is not set, 
     * 'default' route is assumed, not current!
     * When passing path as array, current route is used (this works
     * exactly like Zend helper implementation).
     *
     * @param string|array $urlOptions  Options passed to the assemble method of the Route object.
     * @param mixed $name               The name of a Route to use. If null it will use the
     *                                  current Route if urlOptions is array, if urlOptions is string
     *                                  default Route will be used.
     * @param bool $reset               Whether or not to reset the route defaults with those provided
     * @return string                   Url for the link href attribute.
     */
    public function url()
    {
        $callback = array('parent', 'url');
        $args = func_get_args();
        if (isset($args[0]) && !is_array($args[0])) {
            $args[0] = Zefram_Url::toArray($args[0]);
            if (count($args) < 2) {
                // route not given, use default route, not current
                $args[1] = 'default';
            }
        }
        if (self::$_trans_sid) {
            if (!isset($args[0])) {
                $args[0] = array();
            }
            $args[0][session_name()] = session_id();
        }
        return call_user_func_array($callback, $args);
    }
}
