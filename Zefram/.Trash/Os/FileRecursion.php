<?php

require_once dirname(__FILE__) . '/FileRecursion/Callable.php';
require_once dirname(__FILE__) . '/FileRecursion/Exception.php';

class Zefram_Os_FileRecursion
{
    protected $_dir;
    protected $_callable;
    protected $_match = array(
        'file' => array(
            'match'  => null,
            'invert' => null,
        ),
        'dir'  => array(
            'match'  => null,
            'invert' => null,
        ),
    );
    protected $_log;

    public static function create($options = array())
    {
        return new self($options);
    }

    public function __construct($options = array())
    {
        foreach ($options as $key => $value) {
            $setter = 'set' . $key;
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            }
        }
        if (empty($this->_dir)) {
            $this->setDir('.');
        }
        if (empty($this->_callable)) {
            $this->setCallable(null);
        }
    }

    public function setDir($dir)
    {
        if (!is_dir($dir) || !is_readable($dir)) {
            throw new Zefram_Os_FileRecursion_Exception("Not a directory or directory is unreadable: $dir");
        }
        $this->_dir = str_replace('\\', '/', realpath($dir));
    }

    public function setDirMatch($patterns)
    {
        $this->_match['dir']['match'] = $this->_sanitizePatterns($patterns);
    }

    public function setDirInvertMatch($patterns)
    {
        $this->_match['dir']['invert'] = $this->_sanitizePatterns($patterns);
    }

    public function setMatch($patterns)
    {
        $this->_match['file']['match'] = $this->_sanitizePatterns($patterns);
    }

    public function setInvertMatch()
    {
        $this->_match['file']['invert'] = $this->_sanitizePatterns($patterns);
    }

    public function setCallable($callable)
    {
        if ((is_object($callable) && $callable instanceof FileRecursion_Callable) || is_callable($callable)) {
            $this->_callable = $callable;
        } else {
            throw new Zefram_Os_FileRecursion_Exception("Invalid callable specified");
        }
    }

    public function setLog($logFile)
    {
        if ($this->_log) {
            fclose($this->_log);
            $this->_log = null;
        }

        $log = @fopen($logFile, "w");
        if (false === $log) {
            throw new Zefram_Os_FileRecursion_Exception("Unable to open log file '$logFile' for writing");
        }
        $this->_log = $log;
    }

    public function process()
    {
        $this->_process($this->_dir);
        if ($this->_log) {
            fclose($this->_log);
            $this->_log = null;
        }
    }

    protected function _process($path)
    {
        // path with respect to start directory
        $relpath = substr($path, strlen($this->_dir) + 1);
        $this->_log("%s: ", $relpath);

        if (is_dir($path) && $this->_canProcess('dir', $path)) {
            $this->_log("descending into directory\n");
            foreach (scandir($path) as $entry) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                $this->_process($path . '/' . $entry);
            }
        } elseif (is_file($path) && $this->_canProcess('file', $path)) {
            if ($this->_callable instanceof FileRecursion_Callable) {
                $result = $this->_callable->call($path, $relpath);
            } else {
                $callable = $this->_callable;
                $result = $callable($path, $relpath);
            }
            $this->_log("processing returned %s\n", $result);
        } else {
            $this->_log("omitted\n");
        }
    }

    protected function _sanitizePatterns($patterns)
    {
        $result = array();
        foreach ((array) $patterns as $pattern) {
            $pattern = (string) $pattern;
            if (false === preg_match($pattern, '')) {
                throw new Zefram_Os_FileRecursion_Exception('Invalid pattern specified: ' . $pattern);
            }
            $result[] = $pattern;
        }
        return $result;
    }

    protected function _match($patterns, $value)
    {
        foreach ((array) $patterns as $pattern) {
            if (!preg_match($pattern, $value)) {
                return false;
            }
        }
        return true;
    }

    protected function _canProcess($type, $path)
    {
        return $this->_match($this->_match[$type]['match'], $path)
            && (empty($this->_match[$type]['invert']) || !$this->_match($this->_match[$type]['invert'], $path));
    }

    protected function _log($message)
    {
        if ($this->_log) {
            $args = func_get_args();
            array_unshift($args, $this->_log);
            call_user_func_array('fprintf', $args);
        }
    }
}
