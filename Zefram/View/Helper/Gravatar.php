<?php

/**
 * Gravatar view helper replacement that allow getting URL of gravatar image.
 *
 * @package Zefram_View
 * @subpackage Helper
 */
class Zefram_View_Helper_Gravatar extends Zend_View_Helper_Gravatar
{
    /**
     * Get avatar url.
     *
     * This method does not change the state of the view helper.
     *
     * @param  string|array $email OPTIONAL
     * @param  array $options OPTIONAL
     * @return string
     */
    public function getUrl($email = null, array $options = null)
    {
        // replace options with email if the latter is an array
        if (is_array($email)) {
            $options = $email;
            $email = null;
        }

        // set email and options, but save original values for
        // later restoration
        if (null !== $email) {
            $emailOrig = $this->getEmail();
            $this->setEmail($email);
        }

        if ($options) {
            $optionsOrig = $this->_options;
            $this->setOptions((array) $options);
        }

        // retrieve avatar url
        $url = $this->_getAvatarUrl();

        // restore original state
        if (isset($emailOrig)) {
            $this->setEmail($emailOrig);
        }

        if (isset($optionsOrig)) {
            $this->_options = $optionsOrig;
        }

        return $url;
    }
}
