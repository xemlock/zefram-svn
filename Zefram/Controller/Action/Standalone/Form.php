<?php

/**
 * Form handling logic
 * @depends Zend_Form
 */
class Zefram_Controller_Action_Standalone_Form extends Zefram_Controller_Action_Standalone_Abstract
{
    const XMLHTTP_TYPE_FORM_MARKUP = 'FormHTML';
    const XMLHTTP_TYPE_FORM_ERRORS = 'FormErrors';

    protected $_form;

    /**
     * if second arg is instance of Zend_Form it is used as a form,
     * else a new form is created with initForm, with params counting from second
     * are passed to it.
     * When overriding constructor in subclesses call it with Zend_Form
     * parameter to avoid additional form creation.
     */
    public function __construct(Zend_Controller_Action $controller)
    {
        parent::__construct($controller);

        if (func_num_args() > 1 && ($form = func_get_arg(1)) instanceof Zend_Form) {
            $this->_form = $form;
        } else {
            $args = func_get_args();
            array_shift($args);
            $this->_form = call_user_func_array(array($this, 'initForm'), $args);
        }
    }

    public function getForm()
    {
        return $this->_form;
    }
    
    protected function _process(array $values)
    {}

    public function init() 
    {
        parent::init();
        $this->_bulletin = Bulletin::fetchBulletin($this->_getParam('b'));        
    }

    protected function _processPartial(array $partialValues)
    {
        if (count($partialValues)) {
            $editable = array('title', 'description');
            foreach ($editable as $column) {
                if (isset($partialValues[$column])) {
                    $this->_bulletin->$column = $partialValues[$column];
                }
            }
            $this->_bulletin->save();
            Bulletin::clearCache();
            return $this->_bulletin->toArray();
        }
    }

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

    public function run()
    {
        $form = new Zefram_Form;
        $form->addElement('text', 'title', array('required' => true));
        $form->addElement('text', 'description');
        
        // config -----------------------------------------------------
        $partial = true;
        $requireAjax = true;
        $ajaxResponse = self::AJAX_FORM_MARKUP;

        // form handling and rendering --------------------------------
        $request = $this->getRequest();
        $controller = $this->getController();
        $isAjax  = $requireAjax || $request->isXmlHttpRequest();

        $this->_form = $form;
        $view = $this->getView();
        $view->form = $form;

        if (($submitData = $this->_getSubmitData()) !== null) {
            $validData = null;
            $processed = false;
            try {
                if ($partial) {
                    $validData = $this->_form->getValidValues($submitData);
                    if (count($validData)) {
                        $result = $this->_processPartial($validData);
                        $processed = true;
                    }
                } else {
                    if ($this->_form->isValid($submitData)) {
                        $result = $this->_process($this->_form->getValues());
                        $processed = true;
                    }
                }

                // string result is considered as a redirect uri
                if ($isAjax) {
                    if ($processed) {
                        $response = array('status' => 'ok');
                        if (!empty($result)) {
                            $response['data'] = $result;
                        }
                        return $this->_json($response);
                    } else {
                        // if accessed with AJAX rend proper response
                        $response = array('status' => 'error');

                        switch ($ajaxResponse) {
                            case self::AJAX_FORM_MARKUP:
                                $response['type'] = 'FormHTML';
                                $view->doctype('XHTML1_STRICT');
                                $form->setView($view);
                                $response['data'] = '<html>' . $form->__toString() . '</html>';
                                break;

                            case self::AJAX_FORM_ERRORS:
                                $response['type'] = 'FormErrors';
                                $response['data'] = array(
                                    'form'     => $form->getCustomMessages(),
                                    'elements' => $this->_getErrorMessages($form),
                                );
                                break;
                        }
                        return $this->_json($response);
                    }
                } else {
                    // redirect

                }

            } catch (Exception $e) {
                if ($isAjax) {
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
