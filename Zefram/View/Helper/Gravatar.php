<?php

/**
 * Gravatar view helper replacement that allow getting URL of gravatar image.
 *
 * @package Zefram_View
 */
class Zefram_View_Helper_Gravatar extends Zend_View_Helper_Gravatar
{
    public function getUrl($email, array $options = null)
    {
        $this->setEmail($email);
        $this->setOptions($options);
        return $this->_getAvatarUrl();
    }
}
