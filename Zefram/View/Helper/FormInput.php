<?php

/**
 * INPUT tag renderer.
 *
 * @version 2014-02-12
 */
class Zefram_View_Helper_FormInput extends Zend_View_Helper_FormElement
{
    /**
     * Generates an input element.
     *
     * @param  array|string|Zend_Form_Element $name
     * @param  mixed $value OPTIONAL
     * @param  array $attribs OPTIONAL
     * @return string The element XHTML.
     */
    public function formInput($name, $value = null, array $attribs = null)
    {
        // if the $name parameter is an array, it is expected to be the only
        // parameter passed to this function specifying all options
        if (is_array($name)) {
            $attribs = $name;
            $value = null;
            $name = isset($attribs['name']) ? $attribs['name'] : null;
        }

        // if the $value parameter is an array, it replaces the $attribs
        // parameter, as an input value must be scalar
        if (is_array($value)) {
            $attribs = $value;
            $value = null;
        }

        // if value is stored in the $attribs parameter, extract it
        // and replace $value parameter
        if (is_array($attribs) && array_key_exists('value', $attribs)) {
            $value = $attribs['value'];
            unset($attribs['value']);
        }

        if ($name instanceof Zend_Form_Element) {
            $element = $name;
            $elementValue = $element->getValue();

            if (null !== $value) {
                // If $value parameter is given, assume the element is to be
                // rendered as a checkable input (checkbox or radio). Compare
                // the element's value with the given one and if they are equal
                // (both values must not be NULL) add 'checked' attribute.
                // This behaviour is particularily useful when manually
                // rendering radio/checkbox inputs based on Zend_Form_Element.
                // Values are compared as strings to avoid implicit conversion
                // when one of them is a number.
                if (null !== $elementValue && strval($value) === strval($elementValue)) {
                    $attribs['checked'] = 'checked';
                }
            } else {
                // empty $value parameter means there is not value to compare
                // against. Simply use element's value when rendering input tag.
                $value = $elementValue;
            }
            $name = $element->getFullyQualifiedName();
        }

        $info = $this->_getInfo($name, $value, $attribs);
        extract($info); // name, id, value, attribs, options, listsep, disable

        if (array_key_exists('type', $attribs)) {
            $type = $attribs['type'];
            unset($attribs['type']);
        }

        if (empty($type)) {
            $type = 'text';
        }

        // don't show values on password inputs
        if ($type === 'password') {
            $value = '';
        }

        $disabled = '';
        if ($disable) {
            $disabled = ' disabled="disabled"';
        }

        // build the element
        $xhtml = '<input '
                . ' type="' . $this->view->escape($type) . '"'
                . ' name="' . $this->view->escape($name) . '"'
                . ' id="' . $this->view->escape($id) . '"'
                . ' value="' . $this->view->escape($value) . '"'
                . $disabled
                . $this->_htmlAttribs($attribs)
                . $this->getClosingBracket();

        return $xhtml;
    }
}
