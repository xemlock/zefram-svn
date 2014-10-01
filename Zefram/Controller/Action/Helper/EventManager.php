<?php

/**
 * This helper provides an event manager available to the controller
 * action during dispatch workflow.
 *
 * @version 2014-10-01
 * @author xemlock
 */
class Zefram_Controller_Action_Helper_EventManager
    extends Zend_Controller_Action_Helper_Abstract
    implements Zend_EventManager_SharedEventCollectionAware
{
    /**
     * @var Zend_EventManager_EventManager
     */
    protected $_instances = array();

    /**
     * @var Zend_EventManager_SharedEventCollection
     */
    protected $_sharedCollections;

    /**
     * Get shared event collections container
     *
     * @return Zend_EventManager_SharedEventCollection
     */
    public function getSharedCollections()
    {
        if ($this->_sharedCollections === null) {
            $this->_sharedCollections = Zend_EventManager_StaticEventManager::getInstance();
        }
        return $this->_sharedCollections;
    }

    /**
     * Set shared event collections container
     *
     * @param  Zend_EventManager_SharedEventCollection $sharedManager
     * @return Zefram_Controller_Action_Helper_EventManager
     */
    public function setSharedCollections(Zend_EventManager_SharedEventCollection $sharedCollections)
    {
        $this->_sharedCollections = $sharedCollections;
        foreach ($this->_instances as $eventManager) {
            $eventManager->setSharedCollections($sharedCollections);
        }
        return $this;
    }

    /**
     * Creates an event manager instance
     *
     * @param  object $owner OPTIONAL
     * @return Zend_EventManager_EventManager
     */
    public function createEventManager($owner = null)
    {
        $eventManager = new Zend_EventManager_EventManager();
        if (is_object($owner)) {
            $eventManager->setIdentifiers(
                array(get_class($owner)) + array_values(class_parents($owner))
            );
        }
        return $eventManager;
    }

    /**
     * Retrieve event manager for current action controller
     *
     * @return Zend_EventManager_EventManager
     * @throws Zend_Controller_Action_Exception
     */
    public function getEventManager()
    {
        $controller = $this->getActionController();
        if (empty($controller)) {
            throw new Zend_Controller_Action_Exception('No action controller is set');
        }
        $id = spl_object_hash($controller);
        if (empty($this->_instances[$id])) {
            $this->_instances[$id] = $this->createEventManager($controller);
        }
        return $this->_instances[$id];
    }

    /**
     * {@inheritDoc}
     *
     * Removes any previously registered event manager for current
     * action controller.
     *
     * @return void
     */
    public function postDispatch()
    {
        $controller = $this->getActionController();
        if ($controller) {
            $id = spl_object_hash($controller);
            unset($this->_instances[$id]);
        }
    }

    /**
     * Proxy to {@link getEventManager()}
     */
    public function direct()
    {
        return $this->getEventManager();
    }
}
