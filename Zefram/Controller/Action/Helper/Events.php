<?php

/**
 * @version 2014-09-26
 */
class Zefram_Controller_Action_Helper_Events
    extends Zend_Controller_Action_Helper_Abstract
    implements Zend_EventManager_EventManagerAware
{
    /**
     * @var Zend_EventManager_EventCollection
     */
    protected $_events;

    /**
     * Retrieves event manager
     *
     * @return Zend_EventManager_EventCollection
     */
    public function getEventManager()
    {
        if (!$this->hasEventManager()) {
            $this->setEventManager($this->createEventManager());
        }
        return $this->_events;
    }

    /**
     * Sets event manager
     *
     * @param  $events
     * @return Zefram_Controller_Action_Helper_Events
     */
    public function setEventManager(Zend_EventManager_EventCollection $events)
    {
        $this->_events = $events;
        return $this;
    }

    /**
     * Is event manager set
     *
     * @return Zefram_Controller_Action_Helper_Events
     */
    public function hasEventManager()
    {
        return ($this->_events instanceof Zend_EventManager_EventCollection);
    }

    /**
     * Creates an event manager instance
     *
     * Newly created event manager gets the same shared event collection
     * as the helper's event manager.
     *
     * @return Zend_EventManager_EventManager
     */
    public function createEventManager()
    {
        $events = new Zend_EventManager_EventManager();

        if ($this->_events instanceof Zend_EventManager_EventManager) {
            $events->setSharedCollections($events->getSharedCollections());
        }

        return $events;
    }

    /**
     * {@inheritDoc}
     *
     * This routine initializes action controller's event manager, provided
     * that the controller supports it. This method is executed before
     * controller's preDispatch(), so event manager becomes available as
     * early as possible in the action lifetime.
     *
     * @return void
     */
    public function preDispatch()
    {
        $controller = $this->getActionController();
        if ($controller instanceof Zend_EventManager_EventManagerAware) {
            $controller->setEventManager($this->createEventManager());
        }
    }
}
