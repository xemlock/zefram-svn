<?php

/**
 * @package   Zefram_Validate
 * @uses      Zend_Validate
 * @uses      Zefram_Url
 * @author    xemlock
 * @version   2013-07-21
 */
class Zefram_Validate_Url extends Zend_Validate_Abstract
{
    const INVALID_URL = 'invalidUrl';

    /**
     * List of allowed schemes.
     *
     * @var array
     */
    protected $_scheme = array('http', 'https');

    protected $_messageTemplates = array(
        self::INVALID_URL => "'%value%' is not a valid URL.",
    );

    /**
     * @param array|object $options
     */
    public function __construct($options = null)
    {
        if (null !== $options) {
            if (is_object($options) && method_exists($options, 'toArray')) {
                $options = $options->toArray();
            }

            $options = (array) $options;

            if (isset($options['scheme'])) {
                $this->setScheme($options['scheme']);
            }
        }
    }

    /**
     * @param  string|array $scheme
     * @return Zefram_Validate_Url this object
     */
    public function setScheme($scheme)
    {
        if (is_string($scheme) && strpos($scheme, ',') !== false) {
            $scheme = array_map('trim', explode(',', $scheme));
        }
        $this->_scheme = array_map('strtolower', (array) $scheme);
        return $this;
    }

    /**
     * @return string|array
     */
    public function getScheme()
    {
        return $this->_scheme;
    }

    /**
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        $this->_setValue((string) $value);

        if (!Zefram_Url::check($value, $this->_scheme)) {
            $this->_error(self::INVALID_URL);
            return false;
        }

        return true;
    }
}
