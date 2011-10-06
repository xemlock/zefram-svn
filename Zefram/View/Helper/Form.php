<?php

class Zefram_View_Helper_Form extends Zend_View_Helper_Form
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
                ), 
                $form->getAttribs(),
                (array) $attribs
            );
            $form = $form->getFullyQualifiedName();
        }
        return parent::form($form, $attribs, $content);
    }
}
