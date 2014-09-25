<?php

/**
 * Event action helper.
 *
 * This component allows controllers to trigger events. Each controller has its
 * own event manager identified by the class name of this controller (in
 * consequence, two instances of the same controller class will have a common
 * event manager).
 * This helper stores also a shared collection of listeners for use by the
 * controllers' event managers.
 * If a event manager related method is called from outside action controller
 * context an exception will be thrown.
 * 
 * @version 2014-09-25
 */
class Zefram_Controller_Action_Helper_Events
    extends Zend_Controller_Action_Helper_Abstract
    implements Zend_EventManager_EventManagerAware,
               Zend_EventManager_SharedEventCollectionAware
{
    /**
     * @var Zend_EventManager_SharedEventCollection|null
     */
    protected $_sharedCollections;

    /**
     * @var Zend_EventManager_EventManager[]
     */
    protected $_events = array();

    /**
     * @param  Zend_EventManager_SharedEventCollection $collections
     * @return Zefram_Controller_Action_Helper_Events
     */
    public function setSharedCollections(Zend_EventManager_SharedEventCollection $collections = null)
    {
        $this->_sharedCollections = $collections;

        if ($collections === null) {
            foreach ($this->_events as $eventManager) {
                $eventManager->unsetSharedCollections();
            }
        } else {
            foreach ($this->_events as $eventManager) {
                $eventManager->setSharedCollections($collections);
            }
        }

        return $this;
    }

    /**
     * @return Zefram_Controller_Action_Helper_Events|null
     */
    public function getSharedCollections()
    {
        return $this->_sharedCollections;
    }

    /**
     * @return bool
     */
    public function hasEventManager()
    {
        return isset($this->_events[$this->_getEventManagerId()]);
    }

    /**
     * Get event manager for the current action controller.
     *
     * @return Zend_EventManager_EventManager
     */
    public function getEventManager()
    {
        if (!$this->hasEventManager()) {
            $this->setEventManager(new Zend_EventManager_EventManager());
        }
        return $this->_events[$this->_getEventManagerId()];
    }

    /**
     * Set event manager for the current action controller.
     *
     * @param  Zend_EventManager_EventManager $events
     * @return Zefram_Controller_Action_Helper_Events
     */
    public function setEventManager(Zend_EventManager_EventCollection $events)
    {
        $id = $this->_getEventManagerId();

        if ($events instanceof Zend_EventManager_EventManager) {
            $events->setIdentifiers(array($id) + array_values(class_parents($id)));
        }

        $collections = $this->getSharedCollections();
        if ($collections) {
            $events->setSharedCollections($collections);
        }

        $this->_events[$id] = $events;
        return $this;
    }

    /**
     * @return string
     * @throws Zend_Controller_Action_Exception
     */
    protected function _getEventManagerId()
    {
        $controller = $this->getActionController();
        if ($controller === null) {
            throw new Zend_Controller_Action_Exception('Event manager requires an action controller');
        }
        return get_class($controller);
    }

    /**
     * Triggers all listeners for the given event attached to the event manager
     * of the current controller
     *
     * @param  string $event Event instance or name
     * @param  array|ArrayAccess $params Event parameters
     * @param  callable|null $callback   If provided, event propagation will be stopped after TRUE is returned
     * @return Zefram_Controller_Action_Helper_Events
     * @throws Zend_Stdlib_Exception_InvalidCallbackException if invalid callback provided
     */
    public function trigger($event, $params = array(), $callback = null)
    {
        if ($this->hasEventManager() || $this->getSharedCollections()) {
            $this->getEventManager()->trigger($event, $this->getActionController(), $params, $callback);
        }
        return $this;
    }

    /**
     * @param  string|array $event
     * @param  callable $callback
     * @param  int $priority
     * @return Zefram_Controller_Action_Helper_Events
     */
    public function attach($event, $callback, $priority = 1)
    {
        $this->getEventManager()->attach($event, $callback, $priority);
        return $this;
    }
}
