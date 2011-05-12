<?php

abstract class Zefram_Controller_Action_Unit_Form extends Zefram_Controller_Action_Unit_Abstract
{
    protected $_form;
    protected $_buildXmlResponse;

    public function __construct(Zend_Controller_Action $controller, Array $options = array()) 
    {
        if (isset($options['form'])) {
            $this->_form = $options['form'];
            unset($options['form']);
        }
        if (isset($options['buildXmlResponse'])) {
            if (!is_callable($options['buildXmlResponse'])) {
                throw new Exception(sprintf('%s is not a valid callback', implode('::', (array) $options['buildXmlResponse'])));
            }
            $this->_buildXmlResponse = $options['buildXmlResponse'];
            unset($options['buildXmlResponse']);
        }
        parent::__construct($controller);
    }

    abstract public function onSubmit();

    /**
     * Populate form with default values.
     */
    public function populateForm() {}

    public function getForm()
    {
        return $this->_form;
    }

    public function buildXmlResponse(&$response)
    {
        if ($this->_buildXmlResponse) {
            $this->_buildXmlResponse($response);
        }
    }

    /**
     * Logic for form handling.
     */
    public function run()
    {
        $controller = $this->getController();

        $view = $controller->view;
        $viewRenderer = $controller->getHelper('viewRenderer');
        $layout  = $controller->getHelper('layout');
        $request = $controller->getRequest();
        $isAjax  = $request->isXmlHttpRequest();

        $form = $this->getForm();
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                try {
                    $redir = $this->onSubmit();
                    if (!$redir) {
                        // reload page
                        $params = $request->getUserParams();
                        $redir = array($params['controller'], $params['action']);
                        unset($params['controller'], $params['action']);
                        if ($params['module'] == 'default') unset($params['module']);
                        foreach ($params as $key => $value) {
                            $redir[] = $key;
                            $redir[] = $value;
                        }
                        $redir = '/' . implode('/', $redir);
                    }

                    $layout->disableLayout();
                    $viewRenderer->setNoRender();

                    // AFTER SUCCESSFUL SUBMIT
                    if ($isAjax) {
                        // helper->json sends header with proper MIME-type
                        //$this->_helper->json(array('code'=>'200', 'message'=>'Success'));
                        $redirect = $request->getBaseUrl() . $redir;
                        $response = array('code'=>'200', 'message'=>'Success', 'redirect'=>$redirect);
                        $this->buildXmlResponse($response);
                        echo Zend_Json::encode($response);
                    } else {
                        $this->redirect($redir);
                    }
                    return;
                } catch (Zefram_Exception_Ignore $e) {
                    // ignore exception - used to silently pop-out of onSubmit chain
                    // useful when handling multiple-submit form
                } catch (Zefram_Controller_Form_Exception $e) {
                    foreach ($e->getMessages() as $field => $errors) {
                        if (!count($errors)) continue;
                        $where = $form->getElement($field);
                        $msg = '';
                        if (!$where) { 
                            $where = $form; 
                            $msg .= "$field: "; 
                        }
                        $msg .= "Constraint validation failed (" . implode(', ', $errors) . ")";
                        $where->addError($msg);
                    }
                } catch (Exception $e) {
                    $form->addError($e->getMessage());
                }
            }

            // mark erroneous fields
            foreach ($form as $field) {
                if ($field instanceof Zend_Form) {
                    // FIXME przechwytywac bledy
                    continue;
                }

                if ($field->hasErrors()) {
                    $tag = $field->getDecorator('HtmlTag');
                    if (!$tag) continue;
                    $tag->setOption('class', trim($tag->getOption('class') . ' error'));
                }
            }


            // isValid populates form with sent data, so there is no need to call
            // form->populate
            // INVALID DATA
            if ($isAjax) {
                $layout->disableLayout();
                $viewRenderer->setNoRender();
                $view->doctype('XHTML1_STRICT');
                // return json with form
                $response = array('code'=>'400', 'message'=>'Validation error: '.@$error_m, 'xml'=>'<xml>' . $form->__toString() . '</xml>');
                $this->buildXmlResponse($response);
                echo Zend_Json::encode($response);
                return;
            }
        } else {
            // form was not submitted, load default values
            $this->populateForm();
        }

        if ($isAjax) {
            $view->doctype('XHTML1_STRICT');
            $layout->disableLayout();
        }

        $template = $view->getScriptPath($controller->getViewScript());
        if (!file_exists($template)) {
            // show form even if template does not exist
            $form->setView($view); // use XHTML elements when needed
            $content = $form->__toString();
        } else {
            // render form using view template
            $view->form = $form;
            $content = $controller->render();
        }

        // NO INPUT
        if ($isAjax) {
            $response = array('code'=>'200', 'message'=>'OK', 'xml'=>'<xml>' . $content . '</xml>');
            $this->buildXmlResponse($response);
            echo Zend_Json::encode($response);
        } else {
            echo $content;
        }

        // no more rendering here
        $viewRenderer->setNoRender();
    }
}

// vim: et sw=4 fdm=marker
