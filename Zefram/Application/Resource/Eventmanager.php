<?php

/**
 * Config options:
 *
 *   resources.eventManager.identifiers[] = 
 *
 * To create event manager with the default options:
 *
 *   resources.eventManager = 1
 */
class Zefram_Application_Resource_Eventmanager
    extends Zend_Application_Resource_ResourceAbstract
{
    public function init()
    {
        $options = $this->getOptions();
        $eventManager = new Zend_EventManager_EventManager();

        if (isset($options['identifiers'])) {
            $eventManager->setIdentifiers((array) $options['identifiers']);
        }

        // initialize eventManager helper
        Zend_Controller_Action_HelperBroker::getStaticHelper('eventManager')->setSharedCollections(
            $eventManager->getSharedCollections()
        );

        return $eventManager;
    }
}
