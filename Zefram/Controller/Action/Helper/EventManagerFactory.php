<?php

class Zefram_Controller_Action_Helper_EventManagerFactory
    extends Zend_Controller_Action_Helper_Abstract
    implements Zend_EventManager_SharedEventCollectionAware
{
    /**
     * @var Zend_EventManager_SharedEventCollection|null
     */
    protected $_sharedCollections;

    /**
     * @param  Zend_EventManager_SharedEventCollection $collections
     * @return Zefram_Controller_Action_Helper_Events
     */
    public function setSharedCollections(Zend_EventManager_SharedEventCollection $collections = null)
    {
        $this->_sharedCollections = $collections;
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
     * Get event manager for the current action controller.
     *
     * @return Zend_EventManager_EventManager
     */
    public function createEventManager()
    {
        $events = new Zend_EventManager_EventManager();
        
        if (($collections = $this->getSharedCollections()) !== null) {
            $events->setSharedCollections($collections);
        }

        return $events;
    }
}
