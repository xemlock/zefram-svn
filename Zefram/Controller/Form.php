<?php

require_once 'Zefram/Controller/Base.php';

abstract class Zefram_Controller_Form extends Zefram_Controller_Base
{
    protected function _processSentData($form, &$context) {}
    protected function _redirectAfterSave($context) {}

    protected function _prepareXmlResponse(&$response) {}

    protected function _processForm($form, $context = null) // {{{
    {
        $isAjax = $this->_isAjaxRequest();
        if ($this->_request->isPost()) {
            if ($form->isValid($this->_request->getPost())) {
                try {
                    $this->_processSentData($form, $context);
                    if (isset($context['redirect'])) {
                        $redir = $context['redirect'];
                    } else {
                        // deprecated
                        $redir = $this->_redirectAfterSave($context);
                    }
                    if (!$redir) {
                        // reload page
                        $params = $this->_request->getUserParams();
                        $redir = array($params['controller'], $params['action']);
                        unset($params['controller'], $params['action']);
                        if ($params['module'] == 'default') unset($params['module']);
                        foreach ($params as $key => $value) {
                            $redir[] = $key;
                            $redir[] = $value;
                        }
                        $redir = '/' . implode('/', $redir);
                    }

                    // AFTER SUCCESSFUL SUBMIT
                    if ($isAjax) {
                        $this->_helper->viewRenderer->setNoRender();
                        $this->_helper->layout->disableLayout();
                        // helper->json sends header with proper MIME-type
                        //$this->_helper->json(array('code'=>'200', 'message'=>'Success'));
                        $redirect = $this->_request->getBaseUrl() . $redir;
                        $response = array('code'=>'200', 'message'=>'Success', 'redirect'=>$redirect);
                        $this->_prepareXmlResponse($response);
                        echo Zend_Json::encode($response);
                    } else {
                        $this->_redirect($redir);
                        $this->_helper->viewRenderer->setNoRender(true);
                    }
                    return;

                } catch (Doctrine_Validator_Exception $e) { // FIXME to tutaj byc nie powinno!!!
                    // FIXME przeniesc to do Form_Model
                    // FIXME powinno dotyczyc wszystkich powiazanych rekordow a nie tylko glownego
                    foreach($form->getRecord()->getErrorStack() as $field => $errors) {
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
                    $error_m = ': ' . $e->getMessage();
                    $form->addError(get_class($e) . $error_m);
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
            if ($this->_isAjaxRequest()) {
              $this->_helper->layout->disableLayout();
              $this->_helper->viewRenderer->setNoRender();
              $this->view->doctype('XHTML1_STRICT');
              // return json with form
              $response = array('code'=>'400', 'message'=>'Validation error'.@$error_m, 'xml'=>'<xml>' . $form->__toString() . '</xml>');
              $this->_prepareXmlResponse($response);
              echo Zend_Json::encode($response);
              return;
            }
        }


        if ($this->_isAjaxRequest()) {
            $this->view->doctype('XHTML1_STRICT');
            $this->_helper->layout->disableLayout();
        }

        $template = $this->view->getScriptPath($this->getViewScript());
        if (!file_exists($template)) {
            // show form even if template does not exist
            $form->setView($this->view); // use XHTML elements when needed
            $content = $form->__toString();
        } else {
            // render form using view template
            $this->view->form = $form;
            $content = $this->render();
        }

        // NO INPUT
        if ($this->_isAjaxRequest()) {
            $response = array('code'=>'200', 'message'=>'OK', 'xml'=>'<xml>' . $content . '</xml>');
            $this->_prepareXmlResponse($response);
            echo Zend_Json::encode($response);
        } else {
            echo $content;
        }

        // no more rendering here
        $this->_helper->viewRenderer->setNoRender();
    } // }}}

/*
dostepne:
- getRequest
- getViewScript
- view
- getHelper->{viewRenderer}
- render
for ctl specific:
- getForm
- onSubmit
- buildXmlResponse
- getRedirect
- getController 
*/  
    /**
     * Logic for form handling in action within a controller.
     *
     * @param Zend_Controller_Action implementing Zefram_Controller_Form_Control
     */
    public static function processForm(Zefram_Controller_Form_Control $formControl) 
    {
        $controller = $formControl->getController();

        if (!($controller instanceof Zend_Controller_Action)) {
            throw new Exception('Form_Control::getController() must return an instance of Zend_Controller_Action');
        }

        $view = $controller->view;
        $viewRenderer = $controller->getHelper('viewRenderer');
        $layout  = $controller->getHelper('layout');
        $request = $controller->getRequest();
        $isAjax  = $request->isXmlHttpRequest();

        $form = $formControl->getForm();
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {
                try {
                    $formControl->onSubmit();
                    $redir = $formControl->getRedirect();
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
                        $formControl->buildXmlResponse($response);
                        echo Zend_Json::encode($response);
                    } else {
                        $controller->getHelper('redirector')->gotoUrl($redir);
                    }
                    return;

                } catch (Zefram_Controller_Form_Exception $e) {
                    foreach ($e->getMessages() as $field => $errors) {
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
                    $error_m = ': ' . $e->getMessage();
                    echo '<b>' . $error_m . '</b>';
                    $form->addError(get_class($e) . $error_m);
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
                $response = array('code'=>'400', 'message'=>'Validation error'.@$error_m, 'xml'=>'<xml>' . $form->__toString() . '</xml>');
                $formControl->buildXmlResponse($response);
                echo Zend_Json::encode($response);
                return;
            }
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
            $formControl->buildXmlResponse($response);
            echo Zend_Json::encode($response);
        } else {
            echo $content;
        }

        // no more rendering here
        $viewRenderer->setNoRender();
    }

}

// vim: et sw=4 fdm=marker
