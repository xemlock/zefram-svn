<?php

class Zefram_View_Helper_Form extends Zend_View_Helper_FormElement
{
   /**
     * Render HTML form
     *
     * @param  string|Zend_Form $form   Form name or instance
     * @param  null|array $attribs      HTML form attributes
     * @param  false|string $content    Form content
     * @return string
     */
    public function form($form, $attribs = null, $content = false)
    {
        if ($form instanceof Zend_Form) {
            $attribs = array_merge(
                array(
                    'method'  => $form->getMethod(),
                    'action'  => $form->getAction(),
                    'enctype' => $form->getEnctype(),
                    'id'      => $form->getId(),
                ), 
                $form->getAttribs(),
                (array) $attribs
            );
        }

        // ZF version 1.11.1: no closing tag is rendered if content is false
        // ZF version 1.11.11 (maybe earlier): closing tag is rendered always
        // Conclusion: do not rely on parent rendering!

        $xhtml = '<form'
               . $this->_htmlAttribs($attribs)
               . '>';

        if (false !== $content) {
            $xhtml .= $content;
        }

        return $xhtml;
    }
}
