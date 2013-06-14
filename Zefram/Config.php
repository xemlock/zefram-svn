<?php

class Zefram_Config extends Zend_Config
{
    /**
     * Creates a config object based on contents of a given file.
     *
     * @param string     $file      file to process
     * @param string     $section   section to process
     * @param array|bool $options
     * @return Zend_Config
     * @throws Zend_Config_Exception When file cannot be loaded
     * @throws Zend_Config_Exception When section cannot be found in file contents
     */
    public static function factory($file, $section = null, $options = false)
    {
        $suffix = pathinfo($file, PATHINFO_EXTENSION);
        $suffix = strtolower($suffix);

        switch ($suffix) {
            case 'ini':
                $config = new Zefram_Config_Ini($file, $section, $options);
                break;

            case 'xml':
                $config = new Zend_Config_Xml($file, $section, $options);
                break;

            case 'json':
                $config = new Zend_Config_Json($file, $section, $options);
                break;

            case 'yaml':
            case 'yml':
                $config = new Zend_Config_Yaml($file, $section, $options);
                break;

            default:
                throw new Zend_Config_Exception('Invalid configuration file provided; unknown config type: ' . $suffix);
        }

        return $config;
    }


}
