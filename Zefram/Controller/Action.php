<?php

/**
 * @version 2014-09-25
 */
class Zefram_Controller_Action extends Zend_Controller_Action
    // implements Zend_EventManager_EventManagerAware
{
    const EVENT_PRE_DISPATCH  = 'preDispatch';
    const EVENT_POST_DISPATCH = 'postDispatch';

    /*
     *
    // @var Zend_EventManager_EventCollection
    protected $_events;

    public function setEventManager(Zend_EventManager_EventCollection $events)
    {
        if ($events instanceof Zend_EventManager_EventManager) {
            $events->setIdentifiers(array(get_class($this)) + array_values(class_parents($this)));
        }
        $this->_events = $events;
        return $this;
    }

    public function getEventManager()
    {
        if (!$this->_events instanceof Zend_EventManager_EventCollection) {
            $this->setEventManager($this->_helper->eventManagerFactory->createEventManager());
        }
        return $this->_events;
    }

    public function preDispatch()
    {
        $this->_preDispatch();
        $this->getEventManager()->trigger(self::EVENT_PRE_DISPATCH, $this);
    }

    protected function _preDispatch()
    {}

    public function postDispatch()
    {
        $this->_postDispatch();
        $this->getEventManager()->trigger(self::EVENT_POST_DISPATCH, $this);
    }

    protected function _postDispatch()
    {}

     */

    /**
     * Dispatch the requested action
     *
     * @param string $action Method name of action
     * @return void
     */
    public function dispatch($action)
    {
        $this->_helper->events->trigger(self::EVENT_PRE_DISPATCH, compact('action'));
        parent::dispatch($action);
        $this->_helper->events->trigger(self::EVENT_POST_DISPATCH, compact('action'));
    }

    /**
     * @param string|ReflectionClass $controllerClass
     * @param string $actionMethod
     * @return string|false
     */
    public static function loadActionClass($controllerClass, $actionMethod)
    {
        $actionMethod = ucfirst($actionMethod);

        if ($controllerClass instanceof ReflectionClass) {
            $controllerRef = $controllerClass;
            $controllerClass = $controllerRef->getName();
        } else {
            $controllerRef = null;
        }

        $actionClass = $controllerClass . '_' . $actionMethod;

        if (!class_exists($actionClass, false)) {
            // file containing action implementation must reside in the
            // directory having the same name as controller class
            if (null === $controllerRef) {
                $controllerRef = new ReflectionClass($controllerClass);
            }

            $actionDir = $controllerRef->getFileName();

            // strip extension(s) from controller file name to get path
            // to action directory
            if (false !== ($pos = strrpos($actionDir, '.'))) {
                $actionDir = substr($actionDir, 0, $pos);
            }

            $actionFile = $actionDir . '/' . $actionMethod . '.php';

            if (is_file($actionFile) && is_readable($actionFile)) {
                include_once $actionFile;
                if (class_exists($actionClass, false)) {
                    return $actionClass;
                }
            }

            return false;
        }

        return $actionClass;
    }

    public function getActionClass($actionName)
    {
        $controllerClass = get_class($this);
        $actionMethod = ucfirst(preg_replace_callback(
            '/-([a-zA-Z0-9]+)/', 
            create_function('$match', 'return ucfirst($match[1]);'),
            $actionName
        ));
        $actionMethod .= 'Action';

        return self::loadActionClass($controllerClass, $actionMethod);
    }

    public function __call($method, $arguments)
    {        
        if (!strcasecmp(substr($method, -6), 'Action')) {
            // undefined action, try running standalone action
            $actionClass = self::loadActionClass(get_class($this), $method);
            if ($actionClass) {
                $ref = new ReflectionClass($actionClass);
                if ($ref->hasMethod('__construct')) {
                    array_unshift($arguments, $this);
                    $actionObj = $ref->newInstanceArgs($arguments);
                } else {
                    $actionObj = $ref->newInstance($this);
                }
                return $actionObj->run();
            }
        }
        // fallback to default handling of undefined methods
        parent::__call($method, $arguments);
    }

    /**
     * null is not considered a scalar value 
     * (@see http://php.net/manual/en/function.is-scalar.php)
     *
     * @param  string $name
     * @param  mixed $default
     * @param  scalar|null
     * @return mixed
     */
    public function getScalarParam($name, $default = null)
    {
        $value = parent::getParam($name, $default);
        return is_scalar($value) ? $value : $default;
    }

    /**
     * Get resource from container.
     *
     * @param  string $name
     * @return mixed
     */
    public function getResource($name)
    {
        return $this->_helper->resource($name);
    }

    // all below methods are now deprecated
    
    protected $_ajaxResponseClass = 'Zefram_Controller_Action_AjaxResponse';

    protected $_ajaxResponse;

    public function getBootstrap()
    {
        $bootstrap = $this->getFrontController()->getParam('bootstrap');

        if (!$bootstrap instanceof Zend_Application_Bootstrap_BootstrapAbstract) {
            throw new Exception('Bootstrap is not available');
        }

        return $bootstrap;
    }

    /**
     * Proxy to {@see getResource()}.
     *
     * @param  string $name
     * @return mixed
     */
    public function getBootstrapResource($name)
    {
        return $this->getResource($name);
    }


    public function getAjaxResponse()
    {
        if (null === $this->_ajaxResponse) {
            $ajaxResponseClass = $this->_ajaxResponseClass;
            $this->setAjaxResponse(new $ajaxResponseClass);
        }
        return $this->_ajaxResponse;
    }

    public function setAjaxResponse(Zefram_Controller_Action_AjaxResponse_Abstract $ajaxResponse)
    {
        $this->_ajaxResponse = $ajaxResponse;
        return $this;
    }

    // additional proxies to helpers
    /**
     * @deprecated
     */
    protected function _flashMessage($message, $namespace = null)
    {
        return $this->flashMessage($message, $namespace);
    }

    public function flashMessage($message, $namespace = null)
    {
        $this->_helper->flashMessenger->addMessage($message, $namespace);
        return $this;
    }

    /**
     * @throws Zend_Controller_Action_Exception
     */
    public function setLayout($layout)
    {
        $this->_helper->layout->setLayout($layout);
        return $this;
    }

    /**
     * @throws Zend_Controller_Action_Exception
     */
    public function disableLayout($disable = true)
    {
        $layout = $this->_helper->layout;
        if ($disable) {
            $layout->disableLayout();
        } else {
            $layout->enableLayout();
        }
        return $this;
    }

    public function disableView($disable = true)
    {
        $this->_helper->viewRenderer->setNoRender($disable);
        return $this;
    }

    
}
