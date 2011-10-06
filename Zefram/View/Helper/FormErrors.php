<?php

class Zefram_View_Helper_FormErrors extends Zend_View_Helper_FormErrors
{
    /**
     * Render form errors
     *
     * @param  string|array|Zend_Form $source Error(s) or form to render errors from
     * @param  array $options
     * @return string
     */
    public function formErrors($source, array $options = null)
    {
        $form = $source instanceof Zend_Form ? $source : null;
        if ($form) {
            if (!$form->isErrors()) {
                return '';
            }
            // use FormErrors decorator if available
            $formErrors = $form->getDecorator('FormErrors');
            if ($formErrors) {
                $formErrors->setElement($form);
                return $formErrors->render('');
            }
            // show only custom error messages
            $source = $form->getCustomMessages();
        }
        return parent::formErrors($source, $options);
    }
}
