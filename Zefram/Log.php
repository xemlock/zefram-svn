<?php

/**
 * @uses    Zend_Log
 */
class Zefram_Log extends Zend_Log
{
    const EMERGENCY = self::EMERG;
    const CRITICAL  = self::CRIT;
    const ERROR     = self::ERR;
    const WARNING   = self::WARN;

    /**
     * Message format used when logging messages with error handler
     * @var string
     */
    protected $_errorMessageFormat;

    /**
     * @param string $errorMessageFormat
     */
    public function setErrorMessageFormat($errorMessageFormat)
    {
        $this->_errorMessageFormat = (string) $errorMessageFormat;
        return $this;
    }

    /**
     * Set an extra items to pass to the log writers
     *
     * @param  array $extras
     * @return Zefram_Log
     */
    public function setExtras(array $extras)
    {
        foreach ($extras as $name => $value) {
            $this->setEventItem($name, $value);
        }
        return $this;
    }

    /**
     * Log given error and call previously registered error handler
     *
     * @param  int $errno
     * @param  string $errstr
     * @param  string $errfile
     * @param  int $errline
     * @param  array $errcontext
     * @return bool
     */
    public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext)
    {

        $errorLevel = error_reporting();

        if ($errorLevel & $errno) {
            if (isset($this->_errorHandlerMap[$errno])) {
                $priority = $this->_errorHandlerMap[$errno];
            } else {
                $priority = Zend_Log::INFO;
            }

            $message = $this->_errorMessageFormat;
            $extra = array(
                'errno'   => $errno,
                'file'    => $errfile,
                'line'    => $errline,
                'context' => $errcontext,
            );

            if ($message) {
                $vars = array('%message%' => $errstr);

                foreach ($extra as $key => $value) {
                    $vars['%' . $key . '%'] = $value;
                }

                $message = strtr($message, $vars);

            } else {
                $message = $errstr;
            }

            $this->log($message, $priority, $extra);
        }

        if ($this->_origErrorHandler !== null) {
            return call_user_func($this->_origErrorHandler, $errno, $errstr, $errfile, $errline, $errcontext);
        }

        // execute PHP internal error handling
        return false;
    }

    /**
     * Factory to construct logger
     *
     * @param  array|object $config
     * @return Zefram_Log
     */
    public static function factory($config = array())
    {
        if (is_object($config) && method_exists($config, 'toArray')) {
            $config = $config->toArray();
        }

        $config = (array) $config;

        // instantiate log object using given or default class
        if (isset($config['class'])) {
            $log = new $config['class'];
            if (!$log instanceof Zend_Log) {
                throw new Zend_Log_Exception('Log object must be an instanceof Zend_Log');
            }
        } else {
            $log = new self;
        }

        // configure log object using setters
        foreach ($config as $key => $value) {
            $method = 'set' . $key;
            if (method_exists($log, $method)) {
                $log->$method($value);
                unset($config[$key]);
            }
        }

        // register error handler
        if (array_key_exists('registerErrorHandler', $config)) {
            if ($config['registerErrorHandler']) {
                $log->registerErrorHandler();
            }
            unset($config['registerErrorHandler']);
        }

        // writerName is the only required key when setting single writer
        if (isset($config['writerName'])) {
            $log->addWriter($config);
        } else {
            foreach ($config as $writer) {
                $log->addWriter($writer);
            }
        }

        return $log;
    }
}
