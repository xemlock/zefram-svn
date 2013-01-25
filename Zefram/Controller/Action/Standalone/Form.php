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
class Zefram_Controller_Action_Standalone_Form extends Zefram_Controller_Action_Standalone_Abstract
{
    const STATUS_INIT    = 'init';
    const STATUS_OK      = 'ok';
    const STATUS_ERROR   = 'error';

    /**
     * Report form processing errors embedded in the form's markup
     * @var string
     */
    const XMLHTTP_RESPONSE_MARKUP   = 'FormMarkup';

    /**
     * Report form processing errors as an object
     * @var string
     */
    const XMLHTTP_RESPONSE_ERRORS = 'FormErrors';

    /**
     * Use partial instead of full form processing?
     * @var bool
     */
    protected $_processPartial = false;

    /**
     * Allow use of AJAX protocol is access through AJAX
     * @var bool
     */
    protected $_xmlHttpAllowed = true;
    /**
     * Return response suitable for AJAX handling, regardless of whether 
     * action was accessed through AJAX or not
     * @var bool
     */
    protected $_xmlHttpOnly = false;

    /**
     * How form errors should be reported when using AJAX
     * @var string
     */
    protected $_xmlHttpErrorResponseType = self::XMLHTTP_RESPONSE_ERRORS;

    /**
     * Form to process
     * @var Zend_Form
     */
    protected $_form = null;

    /**
     * Was action accessed through AJAX?
     * @var bool
     */
    protected $_isXmlHttp = false;

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

    public function getForm() // {{{
    {
        return $this->_form;
    } // }}}

    /**
     * Creates form to be processed
     *
     * This method gets called only if no form was supplied to the constructor. 
     */
    public function initForm() // {{{
    {
        throw new Zefram_Exception(__METHOD__ . '() is not implemented');
    } // }}}

    /**
     * Process valid form
     *
     * @param array $values     array of form values
     */
    protected function _processForm(array $values) // {{{
    {
        throw new Zefram_Exception(__METHOD__ . '() is not implemented');
    } // }}}

    /**
     * Process partially valid form
     *
     * @param array $partialValues array of valid values
     */
    protected function _processPartialForm(array $partialValues) // {{{
    {
        throw new Zefram_Exception(__METHOD__ . '() is not implemented');
    } // }}}

    /**
     * Method-aware retrieval of submitted form data
     */
    protected function _getSubmitData() // {{{
    {
        $method = $this->_form->getMethod();
        $request = $this->getRequest();

        if (!strcasecmp($method, 'POST') && $request->isPost()) {
            return $request->getPost();
        }
        if (!strcasecmp($method, 'GET') && $request->isGet()) {
            return $request->getQuery();
        }
        return null;
    } // }}}

    /**
     * Redirects the current URL and exits
     */
    protected function _reloadRedirect() // {{{
    {
        $request       = $this->getRequest();
        $params        = $request->getUserParams();

        $moduleKey     = $request->getModuleKey();
        $controllerKey = $request->getControllerKey();
        $actionKey     = $request->getActionKey();

        $module        = $params[$moduleKey];
        $controller    = $params[$controllerKey];
        $action        = $params[$actionKey];

        unset($params[$moduleKey], $params[$controllerKey], $params[$actionKey]);

        $this->getHelper('redirector')->gotoSimple($action, $controller, $module, $params);
    } // }}}

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
     * Logic executed after form was submitted and no exceptions were thrown
     *
     * @param bool $isXmlHttp   whether form was accessed through AJAX
     * @param mixed $result     form processing result
     */
    protected function _onSubmitResponse($isXmlHttp, $result) // {{{
    {
        // do not rely on $this->_isXmlHttp since it can be changed in _process / _processPartial
        if ($isXmlHttp) {
            $xmlHttpResponse = array(
                'status' => self::STATUS_OK,
            );
            // if result of _process or _processPartial is an array,
            // it is attached to AJAX response. If it contains 'status' key, 
            // it will be removed.
            // if it is not an array, place it at 'data' key
            if (is_array($result)) {
                if (isset($result['status'])) {
                    unset($result['status']);
                }
                $xmlHttpResponse = array_merge($xmlHttpResponse, $result);
            } elseif (!empty($result)) {
                $xmlHttpResponse['data'] = $result;
            }
            return $this->_json($xmlHttpResponse);
        }

        // if false do not redirect
        if (false === $result) {
            return;
        }

        // if result is a string, assume it is an url to redirect to,
        // otherwise reload the current URL
        return is_string($result)
             ? $this->_redirect($result)
             : $this->_reloadRedirect();
    } // }}}
    
    /**
     * Execute form handling logic
     */
    public function run()
    {
        $form = $this->_form;
        $request = $this->getRequest();

        $isXmlHttp = $this->_isXmlHttp = $this->_xmlHttpAllowed && ($this->_xmlHttpOnly || $request->isXmlHttpRequest());

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
