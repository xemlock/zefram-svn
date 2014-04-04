<?php

/**
 * SWIFT-BIC validation.
 *
 * @uses    Zend_Validate
 * @uses    Zend_Locale
 * @version 2014-04-04
 * @author  xemlock
 */
class Zefram_Validate_Swift extends Zend_Validate_Abstract
{
    const INVALID          = 'swiftInvalid';
    const INVALID_FORMAT   = 'swiftInvalidFormat';
    const INVALID_COUNTRY  = 'swiftInvalidCountry';
    const COUNTRY_NO_MATCH = 'swiftCountryNoMatch';

    protected $_messageTemplates = array(
        self::INVALID          => 'Value is of invalid type, string expected',
        self::INVALID_FORMAT   => 'Value does not look like a valid SWIFT-BIC code',
        self::INVALID_COUNTRY  => 'Value is in SWIFT-BIC format, but contains invalid country code',
        self::COUNTRY_NO_MATCH => 'The country code of given SWIFT-BIC value differs from %country%',
    );

    protected $_messageVariables = array(
        'country' => '_country',
        'detectedCountry' => '_detectedCountry',
    );

    /**
     * SWIFT-BIC regex
     * @var string
     */
    protected $_regex = '/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/';

    /**
     * ISO 3611 alpha-2 country code to match against
     * @var string
     */
    protected $_country;

    /**
     * Country code detected during validation
     * @var string
     */
    protected $_detectedCountry;

    /**
     * @param  string|null $country
     * @return Zefram_Validate_Swift
     * @throws Zend_Validate_Exception
     */
    public function setCountry($country)
    {
        if (null !== $country) {
            $country = $this->_checkCountry($country);
            if (false === $country) {
                throw new Zend_Validate_Exception('Country must be a valid ISO 3166-1 alpha-2 country code');
            }
        }
        $this->_country = $country;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountry()
    {
        return $this->_country;
    }

    /**
     * @param  string $value
     * @return boolean
     */
    public function isValid($value)
    {
        $this->_setValue($value);
        $this->_detectedCountry = null;

        if (!is_string($value)) {
            $this->_error(self::INVALID);
            return false;
        }

        $value = strtoupper($value);

        if (!preg_match($this->_regex, $value)) {
            $this->_error(self::INVALID_FORMAT);
            return false;
        }

        // 5th and 6th characters contain ISO 3166-1 alpha-2 country code
        $country = $this->_checkCountry(substr($value, 4, 2));
        if (false === $country) {
            $this->_error(self::INVALID_COUNTRY);
            return false;
        }

        $this->_detectedCountry = $country;

        if ((null !== $this->_country) && ($country !== $this->_country)) {
            $this->_error(self::COUNTRY_NO_MATCH);
            return false;
        }

        return true;
    }

    /**
     * @param  string $country
     * @return string|false
     */
    protected function _checkCountry($country)
    {
        try {
            $locale = new Zend_Locale($country);
            $country = $locale->getRegion();

            if (strlen($country)) {
                return $country;
            }

        } catch (Zend_Locale_Exception $e) {
        }

        return false;
    }
}
