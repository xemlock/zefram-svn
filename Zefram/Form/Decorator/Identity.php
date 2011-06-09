<?php

/**
 * A no-op decorator, used to hide form element without deleting it.
 * When empty decorator array is assigned to an element, and loading
 * default decorators on Zend_Form level is not disabled, default
 * decorators will be loaded.
 *
 * @package    Zefram_Form
 * @subpackage Decorator
 */
class Zefram_Form_Decorator_Identity extends Zend_Form_Decorator_Abstract
{
    public function render($content)
    {
        return $content;
    }
}
