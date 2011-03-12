<?php

class Zefram_Controller_Action extends Zend_Controller_Action
{
    public function loadUnitClass($controller, $action)
    {
        $unitClass = $controller . '_' . $action;
        if (!class_exists($unitClass, false)) {
            $frontController = Zend_Controller_Front::getInstance();
            $dir = $frontController->getModuleDirectory()
                 . '/' . $frontController->getModuleControllerDirectoryName();
            $file = $dir . '/' . $controller . '/' . $action . '.php';

            if (file_exists($file)) {
                include_once $file;
            }
            if (!class_exists($unitClass, false)) {
                return null;
            }
        }
        return $unitClass;    
    }

    public function getUnitClass()
    {
        $controllerName = $this->_request->getControllerName();
        $controller = preg_replace('/Controller$/i', '', get_class($this));

        $actionName = $this->_request->getActionName();
        $action = ucfirst(preg_replace_callback(
            '/-([a-zA-Z0-9]+)/', 
            create_function('$matches', 'return ucfirst($matches[1]);'),
            $actionName
        ));

        return $this->loadUnitClass($controller, $action);
    }

    public function __call($method, $arguments)
    {
        if (!strcasecmp(substr($method, -6), 'Action')) {
            // undefined action, try running unit action
            $unitClass = $this->getUnitClass();
            if ($unitClass) {
                $unit = new $unitClass($this, $arguments);
                return $unit->run();
            }        
        }
        // fallback to default handling of undefined methods
        return parent::__call($method, $arguments);
    }
}
