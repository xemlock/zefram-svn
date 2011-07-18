<?php

/**
 * Class for encapsulation of a single action's logic.
 */
abstract class Zefram_Controller_Action_Unit_Abstract extends Zefram_Controller_Action_Standalone_Abstract
{
    // does anybody know why getParam is protected???
    public function getParam($name, $default = null)
    {
        return $this->_getParam($name, $default);
    }

    public function flashMessage($message)
    {
        return $this->_flashMessage($message);
    }

    // does anybody know why _redirect is protected???
    public function redirect($url, array $options = array())
    {
      $this->_redirect($url, $options);
    }
}
