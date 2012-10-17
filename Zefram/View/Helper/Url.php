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
     * Warning: route and reset parameters have different default values than
     * their Zend's couterparts.
     *
     * @param string|array $urlOptions  Options passed to the assemble method of the Route object.
     * @param mixed $name               The name of a Route to use.
     * @param bool $reset               Whether or not to reset the route defaults with those provided
     * @return string                   Url for the link href attribute.
     */
    public function url($urlOptions, $name = 'default', $reset = true)
    {
        if (!is_array($urlOptions)) {
            $urlOptions = Zefram_Url::toArray($urlOptions);            
        }

        if (self::$_trans_sid) {
            $urlOptions[session_name()] = session_id();
        }

        $url = parent::url($urlOptions, $name, $reset);

        return $url;
    }
}
