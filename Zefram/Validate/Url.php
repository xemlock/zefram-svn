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
    const INVALID_URL    = 'invalidUrl';
    const INVALID_SCHEME = 'invalidScheme';

    /**
     * List of allowed schemes.
     *
     * @var array
     */
    protected $_allowedSchemes = array('http', 'https');

    protected $_messageTemplates = array(
        self::INVALID_URL    => "This is not a valid URL",
        self::INVALID_SCHEME => "URL scheme '%scheme%' is not allowed",
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
        if (null !== $options) {
            if (is_object($options) && method_exists($options, 'toArray')) {
                $options = $options->toArray();
            }

            $options = (array) $options;

            if (isset($options['allowedSchemes'])) {
                $this->setAllowedSchemes($options['allowedSchemes']);
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
            $this->_error(self::INVALID_URL);
            return false;
        }

        if (!$uri->valid()) {
            $this->_error(self::INVALID_URL);
            return false;
        }

        $this->_scheme = $uri->getScheme();

        if ($this->_allowedSchemes
            && !in_array($this->_scheme, $this->_allowedSchemes, true)
        ) {
            $this->_error(self::INVALID_SCHEME);
            return false;
        }

        return true;
    }
}
