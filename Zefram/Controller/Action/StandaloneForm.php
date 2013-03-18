<?php

/**
 * Standalone action to handle form related logic.
 * This class provides encapsulation of form-related logic as well as allows
 * avoiding repetitively writing form handling skeleton code.
 *
 * @version    2013-03-18
 * @category   Zefram
 * @package    Zefram_Controller
 * @subpackage Zefram_Controller_Action
 * @copyright  Copyright (c) 2013 Xemlock
 * @license    MIT License
 */
abstract class Zefram_Controller_Action_StandaloneForm extends Zefram_Controller_Action_Standalone
{
    const VALIDATION_FAILED = 'validationFailed';

    const STATUS_SUCCESS = 'success';
    const STATUS_FAIL    = 'fail';
    const STATUS_ERROR   = 'error';

    /**
     * AJAX response statuses.
     * @var string[]
     */
    protected $_ajaxStatuses = array(
        self::STATUS_SUCCESS => self::STATUS_SUCCESS,
        self::STATUS_FAIL    => self::STATUS_FAIL,
        self::STATUS_ERROR   => self::STATUS_ERROR,
    );

    /**
     * Messages used in AJAX responses.
     * @var string[]
     */
    protected $_ajaxMessages = array(
        self::VALIDATION_FAILED => 'Form validation failed.',
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
    protected $_forceAjax = false;

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
     * Process valid form.
     *
     * This method is marked as protected to disallow direct calls
     * when the form is not valid.
     *
     * @return bool|string
     */
    abstract protected function _process();

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
        return $this->_forceAjax || $this->_request->isXmlHttpRequest();
    }

    /**
     * Creates AJAX success response.
     *
     * Response is compatible with, although not strictly conforming to JSend
     * spec {@link http://labs.omniti.com/labs/jsend}. The difference between
     * the spec and this implementation is the presence of the 'message' key,
     * which is not explicitly allowed (it's not explicitly forbidden either)
     * in responses with status other than 'error'.
     *
     * @param string|null $message
     * @param array|null $data
     * @return array
     */
    public function ajaxSuccessResponse($message = null, $data = null)
    {
        $response = array(
            'status' => $this->_ajaxStatuses[self::STATUS_SUCCESS],
        );
        if (null !== $message) {
            $response['message'] = (string) $message;
        }
        $response['data'] = $data;
        return $response;
    }

    /**
     * Creates AJAX fail response.
     *
     * Response is compatible with, although not strictly conforming to JSend
     * spec {@link http://labs.omniti.com/labs/jsend}. The difference between
     * the spec and this implementation is the presence of the 'message' key,
     * which is not explicitly allowed (it's not explicitly forbidden either)
     * in responses with status other than 'error'.
     *
     * @param string $message
     * @param array|null $data
     * @return array
     */
    public function ajaxFailResponse($message, $data = null)
    {
        return array(
            'status'  => $this->_ajaxStatuses[self::STATUS_FAIL],
            'message' => (string) $message,
            'data'    => $data,
        );
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
                // any success response should be sent in processForm() by
                // calling ajaxSuccessResponse() and passing its result to
                // the json action helper
                $result = $this->_processForm();

                // form was handled successfully, perform redirection if not
                // explicitly cancelled by returning false in _processForm()
                if (false !== $result) {
                    // if a string is returned from _processForm() function,
                    // treat it as a redirection url, otherwise use current
                    // request uri
                    if (!is_string($result)) {
                        $result = $this->_request->getRequestUri();
                    }
                    return $this->_helper->redirector->goToUrlAndExit($result);
                }
            }

            if ($isAjax) {
                if ($valid) {
                    // form validated successfully, no redirection performed,
                    // no success response was sent in _processForm()
                    $response = $this->ajaxSuccessResponse();
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

                    $response = $this->ajaxFailResponse(
                        $message,
                        $this->_ajaxFormHtml ? $this->renderForm() : $form->getMessages()
                    );
                }
                return $this->_helper->json($response);
            }
        }

        if ($isAjax) {
            // if form is accessed for the first time return its markup
            $response = $this->ajaxSuccessResponse(null, $this->renderForm());
            return $this->_helper->json($response);
        }

        // mark page as already rendered, append form rendering to response
        $this->_helper->viewRenderer->setNoRender();

        return $this->getResponse()->appendBody($this->renderForm());
    }
}
