<?php

/**
 * Generic URL handler
 *
 * @package   Zefram_Url
 * @uses      Zend_Uri
 * @author    xemlock
 * @version   2013-07-21
 */
abstract class Zefram_Url extends Zend_Uri
{
    /**
     * @param  string $uri
     * @return Zend_Uri_Http
     */
    public static function fromString($uri)
    {
        $uri = explode(':', $uri, 2);

        $scheme = strtolower($uri[0]);
        $schemeSpecific = isset($uri[1]) ? $uri[1] : '';

        // URL scheme pattern based on a list of schemes available at:
        // http://en.wikipedia.org/wiki/URI_scheme

        if (!preg_match('/^[a-z][-._a-z0-9]+$/i', $scheme)) {
            throw new Zend_Uri_Exception('Invalid URL scheme supplied');
        }

        // it turns out that Zend_Uri_Http can handle any correct URL,
        // not only having http(s) scheme.
        // As a notice, all Zend_Uri subclasses are required to have protected
        // contructors.
        return new Zend_Uri_Http($scheme, $schemeSpecific);
    }

    /**
     * @param  string $uri
     * @param  array $schemes list of schemes to validate against
     * @return bool
     */
    public static function check($uri, array $schemes = null)
    {
        try {
            $uri = self::fromString($uri);
        } catch (Exception $e) {
            return false;
        }

        if ($schemes && !in_array($uri->getScheme(), $schemes, true)) {
            return false;
        }

        return $uri->valid();
    }
}
