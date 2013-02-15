<?php

/**
 * Standalone action to handle form related logic.
 * Main reason for existence of this class is to avoid repetitively writing
 * form handling code.
 *
 * @category   Zefram
 * @package    Zefram_Controller
 * @subpackage Zefram_Controller_Action
 * @copyright  Copyright (c) 2011 Xemlock
 * @license    MIT License
 */
class Zefram_Controller_Action_StandaloneForm extends Zefram_Controller_Action_Standalone
{
    const STATUS_SUCCESS = 'success';
    const STATUS_FAIL    = 'fail';
    const STATUS_ERROR   = 'error';

    const FORM_ERRORS    = 1;
    const FORM_HTML      = 2;

    /**
     * JSON response statuses.
     * @var array
     */
    protected $_jsonResponseStatuses = array(
        self::STATUS_SUCCESS => self::STATUS_SUCCESS,
        self::STATUS_FAIL    => self::STATUS_FAIL,
        self::STATUS_ERROR   => self::STATUS_ERROR,
    );

    /**
     * Flags describing form-related fields in JSON response.
     * @var int
     */
    protected $_jsonResponseParts = self::FORM_ERRORS;

    /**
     * Treat every request as an XMLHttpRequest.
     * @var bool
     */
    protected $_forceXmlHttpRequest = false;

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
     * Creates form to be processed
     *
     * This method gets called only if no form was supplied to the constructor. 
     */
    protected function _initForm() // {{{
    {
        throw new Zefram_Exception(__METHOD__ . '() is not implemented');
    } // }}}

    /**
     * Process valid form.
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
    protected function _getForm()
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
     * @throws Zefram_Controller_Action_StandaloneForm_InvalidStateException
     */
    public function getSentData()
    {
        $method = strtoupper($this->_getForm()->getMethod());

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
     * @throws Zefram_Controller_Action_StandaloneForm_InvalidStateException
     */
    public function isFormValid(array $data)
    {
        $form = $this->_getForm();

        if ($this->_processPartialForm) {
            return $form->isValidPartial($data);
        }

        return $form->isValid($data);
    }

    /**
     * @return bool
     */
    public function isXmlHttpRequest()
    {
        return $this->_request->isXmlHttpRequest();
    }

    /**
     * Generate XHTML form markup.
     * XHTML compliance is required in order to avoid complications with processing
     * AJAX response in browsers. (Actually this maybe not sufficent, but thats is
     * the part the developer is responsible for).
     *
     * @param Zend_Form $form form to be rendered
     */
    public static function formXhtml(Zend_Form $form) // {{{
    {
        $view = $form->getView();
        $doctype = $view->getDoctype();
        $view->doctype('XHTML1_STRICT'); // it's the best we can do, without parsing output to a DOM tree
        $xhtml = $form->render();
        $view->doctype($doctype);
        return $xhtml;
    } // }}}

    /**
     * Zend_Form offers no simple way of retrieving validation error messages
     * when custom form errors are set.
     */
    public static function formErrors(Zend_Form $form) // {{{
    {
        $messages = array();
        foreach ($form->getElements() as $name => $element) {
            $eMessages = $element->getMessages();
            if (!empty($eMessages)) {
                $messages[$name] = $eMessages;
            }
        }
        foreach ($form->getSubForms() as $key => $subForm) {
            $fMessages = $this->_getErrorMessages($subForm);
            if (!empty($fMessages)) {
                if ($subForm->isArray()) {
                    $messages[$key] = $fMessages;
                } else {
                    $messages = array_merge($messages, $fMessages);
                }
            }
        }
        return $messages;
    } // }}}

    /**
     * Execute form handling logic
     */
    public function run()
    {
        $isXmlHttpRequest = $this->_forceXmlHttpRequest || $this->isXmlHttpRequest();
        $data = $this->getSentData();

        if (false !== $data) {
            if ($this->isFormValid($data)) {
                $result = $this->_processForm();

                // return false to avoid redirection/reloading,
                // any non-string result will reload current page
                if (is_string($result)) {
                    return $this->_redirect($result);
                } else if (false !== $result) {
                    return $this->_helper->redirector->goToUrlAndExit(
                        $this->_request->getRequestUri()
                    );
                }

            } else if ($isXmlHttpRequest) {
                // form is not valid, AJAX was used, return json response
                $response = array(
                    'status'  => $this->_jsonResponseStatuses[self::STATUS_FAIL],
                    'message' => 'Form validation failed', // TODO Translate
                );
                if ($this->_jsonResponseParts & self::FORM_ERRORS) {
                    $response['data']['formErrors'] = array();
                }
                if ($this->_jsonResponseParts & self::FORM_HTML) {
                    $response['data']['formHtml'] = '';
                }
                return $this->_helper->json($response);
            }
        }

        if (($submitData = $this->_getSubmitData()) !== null) {
            try {
                $isProcessed = false;
                $result = null;

                if ($this->_processPartial) {
                    $validData = $form->getValidValues($submitData);
                    if (count($validData)) {
                        $result = $this->_processPartialForm($validData);
                        $isProcessed = true;
                    }
                } else {
                    if ($form->isValid($submitData)) {
                        $result = $this->_processForm($form->getValues());
                        $isProcessed = true;
                    }
                }
                if ($isProcessed) {
                    return $this->_onSubmitResponse($isXmlHttp, $result);
                } else {
                    if ($isXmlHttp) {
                        // render form markup or return form errors depending on config
                        if (self::XMLHTTP_RESPONSE_HTML === $this->_xmlHttpErrorResponseType) {
                            $xmlHttpResponse = array(
                                'status'  => self::STATUS_ERROR,
                                'type'    => self::XMLHTTP_RESPONSE_MARKUP,
                                'message' => 'Form validation error',
                                'data'    => self::formXhtml($form),
                            );
                        } else {
                            $xmlHttpResponse = array(
                                'status'  => self::STATUS_ERROR,
                                'type'    => self::XMLHTTP_RESPONSE_ERRORS,
                                'message' => 'Form validation error',
                                'data'    => array(
                                    'form'     => $form->getCustomMessages(),
                                    'elements' => $this->_getErrorMessages($form),
                                ),
                            );
                        }
                    }
                }

            } catch (Zefram_Exception_Ignore $e) {
                // ignore exception - used to silently pop-out of processing chain
                // useful when handling multiple-submit form

            } catch (Exception $e) {
throw $e;
                // form processing interrupted by exception
                if ($isXmlHttp) {
                    $response = array(
                        'status'  => self::STATUS_ERROR,
                        'type'    => get_class($e),
                        'message' => $e->getMessage(),
                    );
                    return $this->_json($response);
                } else {
                    // add custom error to form
                    $form->addError($e->getMessage());
                }
            }
        }

        // if generating page response from view, form is set at 'form' property
        $view = $this->getView();

        $controller = $this->getController();
        $template   = $view->getScriptPath($controller->getViewScript());
        if ((false === $template) || !file_exists($template)) {
            // action template does not exist, render only form
            // when accessed through AJAX form markup is XHTML compliant,
            // otherwise is rendered using controller's view
            $content = $isXmlHttp ? self::formXhtml($form) : $form->render($view);
        } else {
            // use template, here we cannot enforce XHTML compliance
            // when accessed through AJAX
            $view->form = $form->setView($view);
            $content = $controller->render();
        }

        $this->getHelper('viewRenderer')->setNoRender();

        if ($isXmlHttp) {
            $response = array(
                'status' => self::STATUS_INIT,
                'type'   => self::XMLHTTP_RESPONSE_MARKUP,
                'data'   => $content,
            );
            return $this->_json($response);
        } else {
            return $this->getResponse()->appendBody($content);
        }
    }
}

// vim: sw=4 et fdm=marker
