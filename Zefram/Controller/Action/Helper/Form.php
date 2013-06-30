<?php

class Zefram_Controller_Action_Helper_Form extends Zend_Controller_Action_Helper_Abstract
{
    public function processForm(Zend_Form $form, $callback, array $options = null)
    {
        $standaloneAction = new Zefram_Controller_Action_Standalone_FormCallback(
            $this->getActionController(), $form, $callback, $options
        );
        $standaloneAction->run();
    }

    /**
     * Proxies to {@see processForm()}.
     */
    public function direct(Zend_Form $form, $callback, array $options = null)
    {
        return $this->processForm($form, $callback, $options);
    }

    /**
     * Proxies to {@see processForm()}.
     */
    public function __invoke(Zend_Form $form, $callback, array $options = null)
    {
        return $this->processForm($form, $callback, $options);
    }
}
