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
    const RESPONSE_OK      = 'ok';
    const RESPONSE_ERROR   = 'error';
    const RESPONSE_DEFAULT = 'default';

    /**
     * Report form processing errors embedded in the form's markup
     * @var string
     */
    const XMLHTTP_RESPONSE_HTML   = 'FormHTML';

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
     * controller, Zend_Form $form
     * controller, array | Zend_Config $options
     * controller, ... - args ... will be passed to initForm
     */
    public function __construct(Zend_Controller_Action $controller) // {{{
    {
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
        parent::__construct($controller);
    } // }}}

    public function initForm() // {{{
    {
        throw new Zefram_Exception(__METHOD__ . '() is not implemented');
    } // }}}

    public function getForm() // {{{
    {
        return $this->_form;
    } // }}}

    /**
     * Process valid form
     *
     * @param array $values array of form values
     */
    protected function _process(array $values) // {{{
    {
        throw new Zefram_Exception(__METHOD__ . '() is not implemented');
    } // }}}

    /**
     * Process partially valid form
     *
     * @param array $partialValues array of valid values
     */
    protected function _processPartial(array $partialValues) // {{{
    {
        throw new Zefram_Exception(__METHOD__ . '() is not implemented');
    } // }}}

    /*
    Browser UI:
        onSuccess:
            redirect to given url (or reloads page)
        onFailure:
            - renderes page with embedded form (ergo does nothing)
    
    AJAX:
        onSuccess:
            return {status: 'ok'} + additional data
        onFailure:
            - form's markup with embedded errors            
            - hash with form errors
    protocol:
        {
            status: 'ok' | 'error' | 'default', <-- form processing status: successfully handled, with errors, initial state
            type:   'FormHTML' | 'FormErrors' | class-name, <-- type of content stored in data: markup - form markup wrapped in <html>, errors - json with errors,
                                                            {form: [], elements: {item: errors}} 
FIXME only toplevel custom errors!!!
            [ data:   mixed ]
        }
            
    */
    const AJAX_FORM_MARKUP = 1;
    const AJAX_FORM_ERRORS = 2;

    /**
     * Zend_Form offers no simple way of retrieving validation error messages
     * when custom form errors are set.
     */
    protected function _getErrorMessages($form) // {{{
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
            return $request->getGet();
        }
        return null;
    } // }}}

    /**
     * Execute form handling logic
     */
    public function run()
    {
        $form = $this->_form;
        $request = $this->getRequest();

        $isXmlHttp = $this->_isXmlHttp = $this->_xmlHttpOnly || $request->isXmlHttpRequest();

        if (($submitData = $this->_getSubmitData()) !== null) {
            $isProcessed = false;
            try {
                if ($this->_processPartial) {
                    $validData = $this->_form->getValidValues($submitData);
                    if (count($validData)) {
                        $result = $this->_processPartial($validData);
                        $isProcessed = true;
                    }
                } else {
                    if ($form->isValid($submitData)) {
                        $result = $this->_process($form->getValues());
                        $isProcessed = true;
                    }
                }

                if ($isXmlHttp) {
                    // if accessed through AJAX return proper response
                    if ($isProcessed) {
                        $response = array('status' => 'ok');
                        // if result of _process or _processPartial is an array,
                        // it is attached to AJAX response. If it contains 'status' key, 
                        // it will be removed.
                        // if it is not an array, place it at 'data' key
                        if (is_array($result)) {
                            if (isset($result['status'])) {
                                unset($result['status']);
                            }
                            $response = array_merge($response, $result);
                        } elseif (!empty($result)) {
                            $response['data'] = $result;
                        }
                    } else {
                        if (self::XMLHTTP_RESPONSE_HTML === $this->_xmlHttpErrorResponseType) {
                            $doctype = $view->getDoctype();
                            $view->doctype('XHTML1_STRICT');
                            $form->setView($view);
                            $response = array(
                                'status' => 'error',
                                'type'   => self::XMLHTTP_RESPONSE_HTML,
                                'data'   => '<html>' . $form->__toString() . '</html>',
                            );
                            $view->doctype($doctype);
                        } else {
                            $response = array(
                                'status' => 'error',
                                'type'   => self::XMLHTTP_RESPONSE_ERRORS,
                                'data'   => array(
                                    'form'     => $form->getCustomMessages(),
                                    'elements' => $this->_getErrorMessages($form),
                                ),
                            );
                        }
                    }
                    return $this->_json($response);
                } else {
                    if ($isProcessed) {
                        // if result is a string, assume it is an url to redirect to
                        if (is_string($result)) {
                            return $this->_redirect($result);
                        } else {
                            // reload page
                        }
                    }
                }

            } catch (Exception $e) {
                if ($isXmlHttp) {
                    return $this->_json(array(
                        'status' => 'error',
                        'type'   => 'exception',
                        'data'   => $e->getMessage(),
                    ));
                } else {
                    $form->addError($e->getMessage());
                }
            }
        }

        
        $controller = $this->getController();
        $view = $this->getView();
        $view->form = $form;
        $template = $view->getScriptPath($controller->getViewScript());
        if ((false === $template) || !file_exists($template)) {
            // show form even if template does not exist
            $form->setView($view); // use XHTML elements when needed
            $content = $form->__toString();
        } else {
            // render form using view template
            $view->form = $form;
            $content = $controller->render();
        }

        if ($isAjax) {
            // no data sent - it means tha form is to be loaded using AJAX - give form's markup in response
            $response = array(
                'status' => 'default',
                'type'   => 'markup',
                'data'   => '<html>' . $form->__toString() . '</html>'
            );
            return $this->_json($response);
        } else {
            echo $content;
        }

        // no more rendering here
        $this->getHelper('viewRenderer')->setNoRender();
    }
}

// vim: sw=4 et fdm=marker
