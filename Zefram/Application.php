<?php

require_once 'Zend/Application.php';

class Zefram_Application extends Zend_Application
{
    /**
     * Initialize application using multiple configuration sources.
     * See {@link Zend_Application::__construct()} for more details.
     *
     * @param string $environment
     * @param string|array|Zend_Config $options,...
     * @throws Zend_Application_Exception When invalid options are provided
     */
    public function __construct($environment)
    {
        parent::__construct($environment);

        $mergedOptions = array();

        for ($i = 1, $n = func_num_args(); $i < $n; ++$i) {
            $options = func_get_arg($i);

            switch (true) {
                case is_string($options):
                    // _loadConfig() is available since ZF 1.8.0
                    $options = $this->_loadConfig($options);
                    break;

                case is_object($options):
                    if (method_exists($options, 'toArray')) {
                        $options = $options->toArray();
                    }
                    break;

                case !is_array($options):
                    throw new Zend_Application_Exception('Invalid options provided; must be location of config file, a config object, or an array');
            }

            // mergeOptions() is available since ZF 1.8.1
            $mergedOptions = $this->mergeOptions($mergedOptions, (array) $options);
        }

        $this->setOptions($mergedOptions);
    }
}
