<?php

/**
 * Resource for initializing logger.
 *
 * Supported configuration options:
 *
 *   resources.log.factoryClass = "Zend_Log"
 * 
 * A single logger:
 *
 *   resources.log.writerName = <WRITER>
 *   resources.log.writerParams.<PARAM> = <VALUE>
 *   resources.log.filterName = <FILTER>
 *   resources.log.filterParams.<PARAM> = <VALUE>
 *
 * Multiple loggers:
 *
 *   resources.log.<FIRST_LOGGER>.writerName = <WRITER>
 *   resources.log.<FIRST_LOGGER>.writerParams.<PARAM> = <VALUE>
 *   resources.log.<FIRST_LOGGER>.filterName = <FILTER>
 *   resources.log.<FIRST_LOGGER>.filterParams.<PARAM> = <VALUE>
 *   resources.log.<SECOND_LOGGER>.writerName = <WRITER>
 *   resources.log.<SECOND_LOGGER>.writerParams.<PARAM> = <VALUE>
 *   resources.log.<SECOND_LOGGER>.filterName = <FILTER>
 *   resources.log.<SECOND_LOGGER>.filterParams.<PARAM> = <VALUE>
 *
 * Options supported by Zefram_Log::factory(), if set as a factoryClass:
 *
 *   resources.log.class = <CLASS>
 *   resources.log.errorMessageFormat = <FORMAT>
 *   resources.log.registerErrorHandler = 0
 */
class Zefram_Application_Resource_Log extends Zend_Application_Resource_ResourceAbstract
{
    protected $_log;
    protected $_factoryClass = 'Zend_Log';

    /**
     * @param  array|object $options
     */
    public function __construct($options = null)
    {
        if (null !== $options) {
            if (is_object($options) && method_exists($options, 'toArray')) {
                $options = $options->toArray();
            }

            $options = (array) $options;

            if (array_key_exists('factoryClass', $options)) {
                $factoryClass = $options['factoryClass'];
                $refClass = new ReflectionClass($factoryClass);
                $factory = $refClass->getMethod('factory');

                if (!$factory || !$factory->isStatic()) {
                    throw new Zend_Log_Exception('Log factory class must implement a static factory() method');
                }

                $this->_factoryClass = $factoryClass;
                unset($options['factoryClass']);
            }
        }

        parent::__construct($options);
    }

    public function init()
    {
        return $this->getLog();
    }

    /**
     * @param  object $log
     * @return Zefram_Application_Resource_Log
     */
    public function setLog($log)
    {
        $this->_log = $log;
        return $this;
    }

    /**
     * Retrieve logger object.
     *
     * @return mixed
     */
    public function getLog()
    {
        if (null === $this->_log) {
            $factoryClass = $this->_factoryClass;
            $options = $this->getOptions();
            $log = $factoryClass::factory($options);
            $this->setLog($log);
        }
        return $this->_log;
    }
}
