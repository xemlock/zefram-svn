<?php

class Zefram_Controller_Action extends Zend_Controller_Action
{
    public function loadUnitClass($controller, $action)
    {
        $unitClass = $controller . '_' . $action;
        if (!class_exists($unitClass, false)) {
            $frontController = Zend_Controller_Front::getInstance();
            $controllerDirectories = $frontController->getControllerDirectory();

            $module = $this->_request->getModuleName();
            if (empty($module)) {
                $module = $frontController->getDefaultModule();
            }
            $dir = $controllerDirectories[$module];

            // remove module prefix from controller name            
            $controllerName = implode('', array_slice(explode('_', $controller), -1));

            $file = $dir . '/' . $controllerName . '/' . $action . '.php';

            if (is_file($file)) {
                include_once $file;
            }

            if (!class_exists($unitClass, false)) {
                return null;
            }
        }
        return $unitClass;    
    }

    public function getUnitClass($actionName)
    {
        $controller = get_class($this);
        $action = ucfirst(preg_replace_callback(
            '/-([a-zA-Z0-9]+)/', 
            create_function('$match', 'return ucfirst($match[1]);'),
            $actionName
        ));
        $action .= 'Action';

        return $this->loadUnitClass($controller, $action);
    }

    public function __call($method, $arguments)
    {        
        if (!strcasecmp(substr($method, -6), 'Action')) {
            // undefined action, try running unit action
            $unitClass = $this->getUnitClass(substr($method, 0, -6));
            if ($unitClass) {
                $ref = new ReflectionClass($unitClass);
                if ($ref->hasMethod('__construct')) {
                    $unit = $ref->newInstanceArgs(array_merge(array($this), $arguments));
                } else {
                    $unit = $ref->newInstance($this);
                }
                return $unit->run();
            }        
        }
        // fallback to default handling of undefined methods
        parent::__call($method, $arguments);
    }

    // additional proxies to helpers
    /**
     * @deprecated
     */
    protected function _flashMessage($message)
    {
        return $this->flashMessage($message);
    }

    public function flashMessage($message)
    {
        $this->_helper->flashMessenger->addMessage($message);
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
