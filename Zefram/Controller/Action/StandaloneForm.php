<?php

/**
 * Standalone action to handle form related logic.
 * This class provides encapsulation of form-related logic as well as allows
 * avoiding repetitively writing form handling skeleton code.
 *
 * @version    2013-02-16
 * @category   Zefram
 * @package    Zefram_Controller
 * @subpackage Zefram_Controller_Action
 * @copyright  Copyright (c) 2013 Xemlock
 * @license    MIT License
 */
class Zefram_Controller_Action_StandaloneForm extends Zefram_Controller_Action_Standalone
{
    const STATUS_SUCCESS = 'success';
    const STATUS_FAIL    = 'fail';
    const STATUS_ERROR   = 'error';

    const AJAX_FORM_ERRORS = 1;
    const AJAX_FORM_HTML   = 2;

    /**
     * AJAX response statuses.
     * @var array
     */
    protected $_ajaxResponseStatuses = array(
        self::STATUS_SUCCESS => self::STATUS_SUCCESS,
        self::STATUS_FAIL    => self::STATUS_FAIL,
        self::STATUS_ERROR   => self::STATUS_ERROR,
    );

    /**
     * Flags describing which fields to include in validation fail response.
     * @var int
     */
    protected $_ajaxValidationFailResponseParts = self::AJAX_FORM_ERRORS;

    /**
     * Message for validation fail AJAX response.
     * @var string
     */
    protected $_ajaxValidationFailResponseMessage = 'Form validation failed.';

    /**
     * Treat every request as AJAX.
     * @var bool
     */
    protected $_forceAjax = false;

    /**
     * Allow processing of partially valid form?
     * @var bool
     */
    protected $_processPartialForm = false;

    /**
     * Form to process.
     * @var Zend_Form
     */
    protected $_form;

    /**
     * if second arg is instance of Zend_Form it is used as a form,
     * else a new form is created with initForm, with params counting from second
     * are passed to it.
     * When overriding constructor in subclesses call it with Zend_Form
     * parameter to avoid additional form creation.
     * Form can be created in init() method, since it gets called before
     * form initialization.
     * controller, Zend_Form $form
     * controller, array | Zend_Config $options
     * controller, ... - args ... will be passed to initForm
     */
    public function __construct(Zend_Controller_Action $controller) // {{{
    {
        parent::__construct($controller);

        // do not overwrite $this->_form if it was set in constructor
        if (!$this->_form instanceof Zend_Form) {
            $this->_form = null;

            if (func_num_args() > 1) {
                $arg = func_get_arg(1);
                if ($arg instanceof Zend_Form) {
                    $this->_form = $arg;
                } else {
                    if ($arg instanceof Zend_Config) {
                        $arg = $arg->toArray();
                    }
                    if (is_array($arg)) {
                        // read config values from array
                        if (isset($arg['form']) && $arg['form'] instanceof Zend_Form) {
                            $this->_form = $arg['form'];
                        }
                        if (isset($arg['processPartial'])) {
                            $this->_processPartial = (bool) $arg['processPartial'];
                        }
                        if (isset($arg['xmlHttpOnly'])) {
                            $this->_xmlHttpOnly = (bool) $arg['xmlHttpOnly'];
                        }
                        if (isset($arg['xmlHttpErrorResponseType'])) {
                            $this->_xmlHttpErrorResponseType = (string) $arg['xmlHttpErrorResponseType'];
                        }
                    }
                }
            }
            if (null === $this->_form) {
                // call initForm with all but first parameters passed to the constructor
                $args = func_get_args();
                array_shift($args);
                $form = call_user_func_array(array($this, 'initForm'), $args);
                if (!$form instanceof Zend_Form) {
                    throw new Zefram_Exception('initForm() must return an instance of Zend_Form');
                }
                $this->_form = $form;
            }
        }
    } // }}}

    /**
     * Creates form to be processed.
     *
     * This method gets called only if no form was supplied to the constructor. 
     */
    protected function _initForm() // {{{
    {
        throw new Zefram_Exception(__METHOD__ . '() is not implemented');
    } // }}}

    /**
     * Process valid form.
     *
     * This method is marked as protected to disallow direct calls
     * when the form is not valid.
     *
     * @return bool|string
     */
    abstract protected function _processForm();

    /**
     * @return Zend_Form
     * @throws Zefram_Controller_Action_StandaloneForm_InvalidStateException
     */
    public function getForm()
    {
        if (!$this->_form instanceof Zend_Form) {
            throw new Zefram_Controller_Action_StandaloneForm_InvalidStateException('_form property was not properly initialized.');
        }
        return $this->_form;
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
            return $this->_request->getQuery();
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
        return $this->_forceAjax || $this->_request->isXmlHttpRequest();
    }

    /**
     * Creates success response conforming to JSend format (@see http://labs.omniti.com/labs/jsend).
     * $message
     * $message, $data
     * $data
     *
     * @param string $message
     * @param array $data
     * @return array
     */
    public function ajaxSuccessResponse($message = null, $data = null)
    {
        $response = array(
            'status' => $this->_ajaxResponseStatuses[self::STATUS_SUCCESS],
        );

        if (is_string($message)) {
            $response['message'] = $message;
            $response['data'] = $data;
        } else {
            $response['data'] = $message;
        }

        return $response;
    }

    /**
     * Creates fail response conforming to JSend format (@see http://labs.omniti.com/labs/jsend).
     * $message
     * $message, $code
     * $message, $data,
     * $message, $code, $data
     *
     * @param string $message
     * @param scalar $code
     * @param array $data
     * @return array
     */
    public function ajaxFailResponse($message, $code = null, $data = null)
    {
        $response = array(
            'status' => $this->_ajaxResponseStatuses[self::STATUS_FAIL],
            'message' => (string) $message,
        );

        if (is_scalar($code)) {
            $response['code'] = $code;
            $response['data'] = $data;
        } else {
            $response['data'] = $data;
        }

        return $response;
    }

    /**
     * Render form.
     *
     * @return string
     */
    public function renderForm()
    {
        $view = $this->view;
        $form = $this->getForm()->setView($view);

        $controller = $this->getController();
        $script = $view->getScriptPath($controller->getViewScript());

        if (!is_file($script)) {
            // if action template does not exist, render form directly
            $content = $form->render();
        } else {
            // if rendering template form is set at 'form' variable
            $view->form = $form;
            $content = $view->render($script);
        }

        return $content;
    }

    /**
     * Execute form handling logic
     */
    public function run()
    {
        $isAjax = $this->isAjax();

        $data = $this->getSentData();
        $form = $this->getForm();

        if (false !== $data) {
            $valid = $this->isFormValid($data);
            if ($valid) {
                // any success response should be issued in processForm() using
                // jsonSuccessResponse() and passed to json action helper
                $result = $this->_processForm();

                // form was handled successfully, perform redirection if not
                // explicitly disallowed by returning false in _processForm()
                if (false !== $result) {
                    // if a string is returned from _processForm() treat it as
                    // a redirection url, otherwise use current request uri
                    if (!is_string($result)) {
                        $result = $this->_request->getRequestUri();
                    }
                    return $this->_helper->redirector->goToUrlAndExit($result);
                }
            }

            if ($isAjax) {
                if ($valid) {
                    // form validated successfully, no redirection performed,
                    // no success message sent in _processForm()
                    $response = $this->ajaxSuccessResponse();
                } else {
                    // the form contains invalid values, if action was accessed
                    // by AJAX, prepare and send response with information
                    // about invalid form values
                    $responseData = null;

                    if ($this->_ajaxValidationFailResponseParts & self::AJAX_FORM_ERRORS) {
                        $responseData['errors'] = $form->getMessages();
                    }
                    if ($this->_ajaxValidationFailResponseParts & self::AJAX_FORM_HTML) {
                        $responseData['html'] = $this->renderForm();
                    }

                    // translate error message using form translator (if any)
                    $message = $this->_ajaxValidationFailResponseMessage;
                    $translator = $form->getTranslator();
                    if ($translator) {
                        $message = $translator->translate($message);
                    }

                    $response = $this->ajaxFailResponse($message, $responseData);
                }
                return $this->_helper->json($response);
            }
        }

        if ($isAjax) {
            $response = $this->ajaxSuccessResponse(array(
                'html' => $this->renderForm(),
            ));
            return $this->_helper->json($response);
        }

        // mark page as already rendered, append form rendering to response
        $this->_helper->viewRenderer->setNoRender();

        return $this->getResponse()->appendBody($this->renderForm());
    }
}
