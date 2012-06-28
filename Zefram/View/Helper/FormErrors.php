<?php

class Zefram_View_Helper_FormErrors extends Zend_View_Helper_FormErrors
{
    /**
     * Render form errors
     *
     * @param  string|array|Zend_Form|Zend_Form_Element $source Error(s) or form to render errors from
     * @param  array $options
     * @return string
     */
    public function formErrors($source, array $options = null)
    {
        switch (true) {
            case $source instanceof Zend_Form:
                if (!$source->isErrors()) {
                    return '';
                }
                // use FormErrors decorator if available
                $formErrors = $source->getDecorator('FormErrors');
                if ($formErrors) {
                    $formErrors->setElement($source);
                    return $formErrors->render('');
                }
                // show only custom error messages
                $source = $source->getCustomMessages();
                break;

            case $source instanceof Zend_Form_Element:
                $source = $source->getMessages();
                break;
        }

        return empty($source)
             ? ''
             : parent::formErrors($source, $options);
    }
}
