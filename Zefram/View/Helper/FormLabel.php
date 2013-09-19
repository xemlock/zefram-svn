<?php

class Zefram_View_Helper_FormLabel extends Zend_View_Helper_FormLabel
{
    /**
     * @param  string|Zend_Form_Element $name
     * @param  string $value
     * @param  array $attribs
     * @return string
     */
    public function formLabel($name, $value = null, array $attribs = null)
    {
        if ($name instanceof Zend_Form_Element) {
            if (null === $value) {
                $value = $name->getLabel();
            }

            if (isset($attribs['class'])) {
                $class = str_replace(
                    array(
                        "\r\n\t",
                        ' required ',
                        ' optional ',
                    ),
                    array(
                        ' ',
                        '',
                        ''
                    ),
                    ' ' . $attribs['class'] . ' '
                );
            } else {
                $class = '';
            }

            if ($name->isRequired()) {
                $class .= 'required';
            } else {
                $class .= 'optional';
            }

            if ('' !== ($class = trim($class))) {
                $attribs['class'] = $class;
            } else {
                unset($attribs['class']);
            }

            $name = $name->getFullyQualifiedName();
        }

        if (isset($attribs['for'])) {
            // override 'for' attribute
            $attribs['disableFor'] = true;
        }

        return parent::formLabel($name, $value, $attribs);
    }
}

