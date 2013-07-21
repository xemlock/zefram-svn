<?php

class Zefram_View_Helper_FormInput extends Zend_View_Helper_FormElement
{
    /**
     * Generates an input element.
     *
     * @return string The element XHTML.
     */
    public function formInput($name, array $attribs = null)
    {
        $value = null;

        if ($name instanceof Zend_Form_Element) {
            // When a value is given assume the element is to be rendered as
            // a checkable input. Compare its value with the given value and
            // if they are equal, add checked attribute.
            if (isset($attribs['value'])) {
                if ($name->getValue() == $attribs['value']) {
                    $attribs['checked'] = 'checked';
                }
                $value = $attribs['value'];
            } else {
                $value = $name->getValue();
            }
            $name = $name->getFullyQualifiedName();
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
