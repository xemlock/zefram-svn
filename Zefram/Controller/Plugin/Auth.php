<?php

/**
 */
class Zefram_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract
{
    protected $_errorModuleName = null;
    protected $_errorControllerName = 'error';

    protected $_auth;
    protected $_acl;

    private $_noauth = array('module'     => 'default',
                             'controller' => 'index',
                             'action'     => 'index',
                       );

    public function __construct(Zend_Auth $auth, Zefram_Acl $acl)
    {
        $this->_auth = $auth;
        $this->_acl = $acl;
    }

    /**
     * @uses   Zefram_Acl::getCurrentRole()
     */
    public function preDispatch($request)
    {
        // nie rzucamy wyjatkow bo w zaleznosci od ustawien (throwExceptions)
        // moze sie okazac, ze dostep do akcji zostanie dany.

        $controller = $request->getControllerName();
        if ($controller == $this->_errorControllerName) {
            if ($this->_errorModuleName) {
                $request->setModuleName($this->_errorModuleName);
            }
            return;
        }

        $action = $request->getActionName();
        $module = $request->getModuleName();
 
        $role = $this->_acl->getCurrentRole();

        if (!$this->_acl->has($controller)) {
            // mimify invalid controller exception
            $request->setParam('error_handler', Zefram_Controller_Plugin_ErrorHandler::createErrorHandler(
                new Zend_Controller_Dispatcher_Exception('Controller not included in ACL (' . $controller . ')', 404)
            ));

            $controller = 'error';
            $action = 'error';

        } else if (!$this->_acl->isAllowed($role, $controller, $action)) {
            // do not forward if AJAX request, report access denied error
            if (!$this->_auth->hasIdentity() && !$request->isXmlHttpRequest()) {
                if ($controller != 'auth' && $controller != 'index') { 
                    $request->setParam('forward', Zefram_Url::serialize($request));
                }
                $module = $this->_noauth['module'];
                $controller = $this->_noauth['controller'];
                $action = $this->_noauth['action'];

            } else {
                $controller = 'error';
                $action = 'error';

                $request->setParam('error_handler', Zefram_Controller_Plugin_ErrorHandler::createErrorHandler(
                    new Zend_Acl_Exception('Insufficient privileges to access this page', 403)
                ));
            }
        }

        $request->setControllerName($controller);
        $request->setActionName($action);
        $request->setModuleName($module);
    }
}
