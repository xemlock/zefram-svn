<?php

class Zefram_Controller_Action extends Zend_Controller_Action
{
    public function runUnit()
    {
        $controllerName = $this->_request->getControllerName();
        $controller = preg_replace('/Controller$/i', '', get_class($this));

        $actionName = $this->_request->getActionName();
        $action = preg_replace_callback(
            '/-([a-zA-Z0-9]+)/', 
            create_function('$matches', 'return ucfirst($matches[1]);'),
            $actionName
        );

        $dir = Zend_Controller_Front::getInstance()->getModuleDirectory() . '/' .
               Zend_Controller_Front::getInstance()->getModuleControllerDirectoryName();
        $file = $dir . '/' . $controller . '/' . $action . '.php';

        include_once $file;

        $unitClass = $controller . '_' . $action;
        if (!class_exists($unitClass, false)) {
            throw new Exception(sprintf('Unit action file not found: %s', $file));
        }

        $args = func_get_args();
        while (count($args) < 5) {
            $args[] = null;
        }
        $unit = new $unitClass($this, $args[0], $args[1], $args[2], $args[3], $args[4]);

        return $unit->run();
    }

}
