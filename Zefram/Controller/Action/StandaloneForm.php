<?php

/**
 * Standalone action to handle form related logic.
 * This class provides encapsulation of form-related logic as well as allows
 * avoiding repetitively writing form handling skeleton code.
 *
 * @version    2013-07-15
 * @category   Zefram
 * @package    Zefram_Controller
 * @subpackage Zefram_Controller_Action
 * @copyright  Copyright (c) 2013 Xemlock
 * @license    MIT License
 */
abstract class Zefram_Controller_Action_StandaloneForm extends Zefram_Controller_Action_Standalone
{
    const VALIDATION_FAILED = 'validationFailed';

    /**
     * Messages used in AJAX responses.
     * @var string[]
     */
    protected $_ajaxMessages = array(
        self::VALIDATION_FAILED => 'Form validation failed',
    );

    /**
     * Return form markup rather than form errors map on failed validation.
     * @var bool
     */
    protected $_ajaxFormHtml = false;

    /**
     * Treat every request as AJAX.
     * @var bool
     */
    protected $_ajaxOnly = false;

    /**
     * Allow processing of partially valid form?
     * @var bool
     */
    protected $_processPartialForm = false;

    /**
     * Form to process, must be initialized in {@see _init()}.
     * @var Zend_Form
     */
    protected $_form;

    /**
     * Name of the view variable the form will be stored in.
     * @var string
     */
    protected $_formKey = 'form';

    /**
     * Process valid form.
     *
     * This method is marked as protected to disallow direct calls
     * when the form is not valid.
     *
     * @return bool|string
     */
    abstract protected function _process();

    /**
     * Method called to populate form with default values when no data
     * is submitted.
     *
     * @return void
     */
    protected function _populate()
    {}

    /**
     * Method called before form validation.
     *
     * @param mixed $data       Data sent to validate form against
     * @return void
     */
    protected function _preValidate($data)
    {}

    /**
     * Method called after form validation.
     *
     * @param bool $isValid     Validation result
     * @param mixed $data       Data sent to validate form against
     * @return bool|void        If a false value is returned form is considered
     *                          invalid
     */
    protected function _postValidate($data)
    {}

    /**
     * @return Zend_Form
     * @throws Zefram_Controller_Action_StandaloneForm_InvalidStateException
     */
    public function getForm()
    {
        if (!$this->_form instanceof Zend_Form) {
            throw new Zefram_Controller_Action_Exception_InvalidState(
                '_form property was not properly initialized.'
            );
        }
        return $this->_form;
    }

    /**
     * Retrieve all form element values.
     *
     * @param bool $suppressArrayNotation
     * @return array
     */
    public function getFormValues($suppressArrayNotation = false)
    {
        return $this->getForm()->getValues($suppressArrayNotation);
    }

    /**
     * Retrieve value for a single form element.
     *
     * @param string $name
     * @return mixed
     */
    public function getFormValue($name)
    {
        return $this->getForm()->getValue($name);
    }

    /**
     * HTTP request method aware retrieval of submitted form data.
     *
     * @return false|array
     */
    public function getSentData()
    {
        $method = strtoupper($this->getForm()->getMethod());

        if ($method == 'POST' && $this->_request->isPost()) {
            return $this->_request->getPost();
        }

        if ($method == 'GET' && $this->_request->isGet()) {
            // consider form as submitted using the GET method only if
            // the request's query part is not empty
            $query = $this->_request->getQuery();
            if (count($query)) {
                return $query;
            }
        }

        return false;
    }

    /**
     * Check if form is valid against given data.
     *
     * @param array $data
     * @return bool
     */
    public function isFormValid(array $data)
    {
        $form = $this->getForm();

        if ($this->_processPartialForm) {
            return $form->isValidPartial($data);
        }

        return $form->isValid($data);
    }

    /**
     * @return bool
     */
    public function isAjax()
    {
        return $this->_ajaxOnly || $this->_request->isXmlHttpRequest();
    }

    /**
     * Retrieves AJAX response.
     *
     * @return Zefram_Controller_Action_AjaxResponse_Abstract
     * @throws Zefram_Controller_Action_Exception_InvalidArgument
     */
    public function getAjaxResponse()
    {
        $ajaxResponse = $this->_helper->ajaxResponse();
        if (!$ajaxResponse instanceof Zefram_Controller_Action_AjaxResponse_Abstract) {
            throw new Zefram_Controller_Action_Exception_InvalidArgument(
                'AjaxResponse must be an instance of Zefram_Controller_Action_AjaxResponse_Abstract'
            );
        }
        return $ajaxResponse;
    }

    /**
     * Renders form.
     *
     * @return string
     */
    public function renderForm()
    {
        $controller = $this->getController();
        $view = $controller->initView();

        $form = $this->getForm()->setView($view);
        $script = $controller->getViewScript();

        if (!is_file($view->getScriptPath($script))) {
            // render form directly if view script does not exist
            $content = $form->render();
        } else {
            $view->assign($this->_formKey, $form);
            $content = $view->render($script);
        }

        return $content;
    }

    /**
     * Retrieve error messages from elements failing validations organized
     * by elements' fully qualified names.
     *
     * @return array
     */
    public function getFormMessages()
    {
        $messages = array();
        $forms = array($this->getForm());

        while ($form = array_shift($forms)) {
            foreach ($form->getElements() as $element) {
                // Element error messages are grouped by fully qualified names
                // so that the corresponding DOM elements may be easily found.
                // Validation error codes are irrelevant for the client-side
                // and so they are, for easier handling by JavaScript, not
                // returned.
                $elementMessages = $element->getMessages();
                if (count($elementMessages)) {
                    $name = $element->getFullyQualifiedName();
                    $messages[$name] = array_values($elementMessages);
                }
            }

            foreach ($form->getSubForms() as $subform) {
                $forms[] = $subform;
            }
        }

        return $messages;
    }

    /**
     * Execute form handling logic
     *
     * @return void
     */
    public function run()
    {
        $isAjax = $this->isAjax();

        $data = $this->getSentData();
        $form = $this->getForm();

        if (false !== $data) {
            $this->_preValidate($data);
            $isValid = $this->isFormValid($data);
            if (false === $this->_postValidate($isValid, $data)) {
                // false returned by _postValidate() invalidates the form
                $isValid = false;
            }
            if ($isValid) {
                try {
                    // any success response should be sent in _process() method
                    // by calling ajaxResponse helper
                    $result = $this->_process();

                    // form was handled successfully, perform redirection if not
                    // explicitly cancelled by returning false in _process()
                    if (false !== $result) {
                        // if a string is returned from the _process() method,
                        // treat it as a redirection url, otherwise use current
                        // request uri
                        if (!is_string($result)) {
                            $result = $this->_request->getRequestUri();
                        }
                        return $this->_helper->redirector->goToUrlAndExit($result);
                    }

                } catch (Zefram_Controller_Action_Exception_FormValidation $e) {
                    // treat any thrown validation exception as a last resort
                    // form validation
                    $element = $form->getElement($e->getElementName());
                    if ($element instanceof Zend_Form_Element) {
                        $element->addErrorMessage($e->getMessage());
                        $element->markAsError();
                    }

                    $isValid = false;
                }
            }

            if ($isAjax) {
                $ajaxResponse = $this->getAjaxResponse();
                if ($isValid) {
                    // form validated successfully, no redirection performed,
                    // no success response was sent in _process()
                    $ajaxResponse->setSuccess();

                } else {
                    // form contains invalid values, send response containing
                    // human-readable message and either full form markup or
                    // form errors map
                    $message = $this->_ajaxMessages[self::VALIDATION_FAILED];

                    // translate error message using form translator (if any)
                    $translator = $form->getTranslator();
                    if ($translator) {
                        $message = $translator->translate($message);
                    }

                    $ajaxResponse->setFail($message);
                    $ajaxResponse->setData(
                        $this->_ajaxFormHtml ? $this->renderForm() : $this->getFormMessages()
                    );
                }
                return $ajaxResponse->sendAndExit();
            }
        } else {
            $this->_populate();
        }

        if ($isAjax) {
            // if form is accessed for the first time return its markup
            $ajaxResponse = $this->getAjaxResponse();
            $ajaxResponse->setSuccess();
            $ajaxResponse->setData($this->renderForm());
            return $ajaxResponse->sendAndExit();
        }

        // mark page as already rendered, so that it isn't auto rendered
        // in viewRenderer::postDispatch(). Append form rendering to
        // response.
        $this->_helper->viewRenderer->setNoRender();

        return $this->getResponse()->appendBody($this->renderForm());
    }
}
