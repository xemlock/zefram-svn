<?php

require_once 'Zefram/Controller/Base.php';

abstract class Zefram_Controller_Form extends Zefram_Controller_Base
{
    protected function _processSentData($form, &$context) {}
    protected function _redirectAfterSave($context) {}

    protected function _prepareXmlResponse(&$response) {}

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
- getController 
*/
    protected static $_trans_sid = false;

    public static function enableTransSid($enable = true)
    {
        self::$_trans_sid = $enable;
    }
    
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
                    $redir = $formControl->onSubmit();
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
                    // FIXME troche to zwalidowac
                    if (self::$_trans_sid) {
                        $redir .= '/' . session_name() . '/' . session_id();
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
