<?php

/**
 * Event trigger action helper.
 *
 * @version 2014-09-24
 */
class Zefram_Controller_Action_Helper_EventTrigger
    extends Zend_Controller_Action_Helper_Abstract
    implements Zend_EventManager_EventManagerAware
{
    /**
     * @var Zend_EventManager_EventCollection
     */
    protected $_eventManager;

    /**
     * Sets an event manager instance.
     *
     * @param  Zend_EventManager_EventCollection $eventManager
     * @return Zefram_Controller_Action_Helper_EventTrigger
     */
    public function setEventManager(Zend_EventManager_EventCollection $eventManager)
    {
        $this->_eventManager = $eventManager;
        return $this;
    }

    /**
     * Retrieves an event manager instance.
     *
     * @return Zefram_Controller_Action_Helper_EventTrigger|null
     */
    public function getEventManager()
    {
        return $this->_eventManager;
    }

    /**
     * Triggers all listeners for a given event.
     *
     * @param  Zend_EventManager_EventDescription|string $event Event instance or name
     * @param  mixed $target             Object calling emit or symbol describing target
     * @param  array|ArrayAccess $params Event parameters
     * @param  callable|null $callback   If provided, event propagation will be stopped after TRUE is returned
     * @return Zefram_Controller_Action_Helper_EventTrigger
     * @throws Zend_Stdlib_Exception_InvalidCallbackException if invalid callback provided
     */
    public function trigger($event, $target = null, $params = array(), $callback = null)
    {
        if (empty($this->_eventManager)) {
            throw new Zend_Controller_Action_Exception('Event manager is not initialized');
        }
        $this->_eventManager->trigger($eventManager, $target, $params, $callback);
        return $this;
    }

    /**
     * Proxy to {@link trigger()}.
     */
    public function direct($event, $target = null, $params = null, $callback = null)
    {
        return $this->trigger($event, $target, $params, $callback);
    }
}
