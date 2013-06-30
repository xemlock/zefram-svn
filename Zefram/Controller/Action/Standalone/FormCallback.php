<?php

class Zefram_Controller_Action_Standalone_FormCallback extends Zefram_Controller_Action_StandaloneForm
{
    /**
     * @var callable
     */
    protected $_callback;

    /**
     * @param  Zend_Controller_Action $actionController
     * @param  Zend_Form $form
     * @param  callable $callback
     * @param  array $options
     * @throws Zefram_Controller_Action_Exception_InvalidArgument
     */
    public function __construct(Zend_Controller_Action $actionController, Zend_Form $form, $callback, $options = null)
    {
        parent::__construct($actionController);

        if (!is_callable($callback)) {
            throw new Zefram_Controller_Action_Exception_InvalidArgument(
                'Callback parametr must be a valid callable'
            );
        }

        $this->_callback = $callback;
        $this->_form = $form;

        if (null !== $options) {
            if (is_object($options) && method_exists($options, 'toArray')) {
                $options = $options->toArray();
            }

            $options = (array) $options;

            if (isset($options['ajaxFormHtml'])) {
                $this->_ajaxFormHtml = (bool) $options['ajaxFormHtml'];
            }

            if (isset($options['forceAjax'])) {
                $this->_forceAjax = (bool) $options['forceAjax'];
            }

            if (isset($options['processPartialForm'])) {
                $this->_processPartialForm = (bool) $options['processPartialForm'];
            }

            if (isset($options['formViewKey'])) {
                $this->_formViewKey = (string) $options['formViewKey'];
            }

            if (isset($options['ajaxMessages']) && is_array($options['ajaxMessages'])) {
                foreach ($options['ajaxMessages'] as $key => $message) {
                    $this->_ajaxMessages[$key] = (string) $message;
                }
            }
        }
    }

    protected function _process()
    {
        return call_user_func($this->_callback, $this->getForm(), $this);
    }
}
