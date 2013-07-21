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
        if ($name instanceof Zend_Form_Element) {
            if (isset($attribs['value'])) {
                $attribs['checked'] = $name->getValue() == $attribs['value'];
            }

            $name = $name->getFullyQualifiedName();
        }

        $info = $this->_getInfo($name, null, $attribs);
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
                . $disabled
                . $this->_htmlAttribs($attribs)
                . $this->getClosingBracket();

        return $xhtml;
    }
}
