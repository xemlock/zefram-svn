<?php

/**
 * @package   Zefram_Validate
 * @uses      Zend_Validate
 * @uses      Zefram_Url
 * @author    xemlock
 * @version   2013-07-21
 *
 * Changelog: 2014-11-20 Added allowLocal option
 */
class Zefram_Validate_Url extends Zend_Validate_Abstract
{
    const INVALID            = 'urlInvalid';
    const SCHEME_NOT_ALLOWED = 'urlSchemeNotAllowed';
    const LOCAL_HOSTNAME     = 'urlLocalHostname';

    /**
     * List of allowed schemes.
     *
     * @var array
     */
    protected $_allowedSchemes = array('http', 'https');

    /**
     * Should local hostnames be allowed.
     *
     * @var bool
     */
    protected $_allowLocal = true;

    protected $_messageTemplates = array(
        self::INVALID            => "This is not a valid URL",
        self::SCHEME_NOT_ALLOWED => "URL scheme '%scheme%' is not allowed",
        self::LOCAL_HOSTNAME     => "Local hostname is not allowed",
    );

    protected $_messageVariables = array(
        'scheme' => '_scheme',
    );

    /**
     * @var string
     */
    protected $_scheme;

    /**
     * @param array|object $options
     */
    public function __construct($options = null)
    {
        if (is_object($options) && method_exists($options, 'toArray')) {
            $options = $options->toArray();
        }

        $options = (array) $options;

        foreach ($options as $key => $value) {
            $method = 'set' . $key;
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
    }

    /**
     * @param  string|array $schemes
     * @return Zefram_Validate_Url this object
     */
    public function setAllowedSchemes($schemes)
    {
        if (is_string($schemes) && strpos($schemes, ',') !== false) {
            $schemes = array_map('trim', explode(',', $schemes));
        }
        $this->_allowedSchemes = array_map('strtolower', (array) $schemes);
        return $this;
    }

    /**
     * @return string|array
     */
    public function getAllowedSchemes()
    {
        return $this->_allowedSchemes;
    }

    /**
     * @param  bool $flag
     * @return Zefram_Validate_Url
     */
    public function setAllowLocal($flag)
    {
        $this->_allowLocal = (bool) $flag;
        return $this;
    }

    /**
     * @return bool
     */
    public function getAllowLocal()
    {
        return $this->_allowLocal;
    }

    /**
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        $value = (string) $value;
        $this->_setValue($value);

        try {
            $uri = Zefram_Url::fromString($value);
        } catch (Exception $e) {
            $this->_error(self::INVALID);
            return false;
        }

        if (!$uri->valid()) {
            $this->_error(self::INVALID);
            return false;
        }

        $this->_scheme = $uri->getScheme();

        if ($this->_allowedSchemes
            && !in_array($this->_scheme, $this->_allowedSchemes, true)
        ) {
            $this->_error(self::SCHEME_NOT_ALLOWED);
            return false;
        }

        // Local domain names may end with a single dot to distinguish them
        // from complete domain names whenever a name conflict arises, see:
        // RFC3986 3.2.2

        $host = $uri->getHost();

        if ((substr($host, -1) === '.' || strpos($host, '.') === false)
            && !$this->_allowLocal
        ) {
            $this->_error(self::LOCAL_HOSTNAME);
            return false;
        }

        return true;
    }
}
