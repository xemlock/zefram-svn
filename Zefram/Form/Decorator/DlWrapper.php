<?php

/**
 * Zefran_Form_Decorator_DlWrapper
 *
 * Wraps the content in a <dl> element. Used as a default wrapper for
 * form elements.
 *
 * @package    Zefram_Form
 * @subpackage Decorator
 */
class Zefram_Form_Decorator_DlWrapper extends Zend_Form_Decorator_HtmlTag
{
    public function render($content)
    {
        $openOnly  = $this->getOption('openOnly');
        $closeOnly = $this->getOption('closeOnly');

        if ($closeOnly) {
            return '</dl>';
        }

        $element = $this->getElement();
        $attribs = $this->getOptions();

        // add id attribute
        if (!isset($attribs['id'])) {
            $attribs['id'] = $element->getId() . '-wrapper';
        }

        // add 'error' class when element has errors
        if ($element->hasErrors()) {
            if (isset($attribs['class'])) {
                $attribs['class'] = (array) $attribs['class'];
            }
            $attribs['class'][] = 'error';
        }

        return '<dl' . $this->_htmlAttribs($attribs) . '>'
             . $content
             . ($openOnly ? '' : '</dl>');
    }
}
