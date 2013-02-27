<?php

/**
 * Ini config, with additional support for hierarchical structures
 * based on section names.
 *
 * @category   Zefram
 * @package    Zefram_Config
 * @copyright  Copyright (c) 2011-2013 xemlock
 * @version    2013-02-27
 */
class Zefram_Config_Ini extends Zend_Config_Ini
{
    public function __construct($filename, $section = null, $options = false) 
    {
        // allow config modifications during initialization
        if (is_bool($options)) {
            $allowModifications = $options;
            $options = true;
        } else {
            $options = (array) $options;
            $allowModifications = isset($options['allowModifications']) && $options['allowModifications'];
            $options['allowModifications'] = true;
        }

        parent::__construct($filename, null, $options);

        // if section's name contains dots, move this section to the 
        // corrseponding position in the tree, e.g.
        //      "a.b.c"
        // will be expanded to
        //      "a"->"b"->"c"
        foreach ($this->_data as $key => $value) {
            if ($value instanceof Zend_Config && false !== strpos($key, '.')) {
                unset($this->_data[$key]);
                $parts = explode('.', $key);
                $ptr = $this;
                while (count($parts)) {
                    $part = array_shift($parts);
                    if (empty($parts)) {
                        $ptr->__set($part, $value);
                    } else {
                        if (null === $ptr->__get($part)) {
                            $ptr->__set($part, new Zend_Config(array(), true));
                        }
                        $ptr = $ptr->__get($part);
                    }
                }
            }
        }

        // load one or more sections
        // this must be done after building tree structure, as some required
        // sections may not necessarily exist earlier
        if (null !== $section) {
            // extract selected sections
            $data = array();
            foreach ((array) $section as $sectionName) {
                if (!isset($this->_data[$sectionName])) {
                    throw new Zend_Config_Exception("Section '$sectionName' cannot be found in $filename");
                }
                $data[$sectionName] = $this->_data[$sectionName];
            }

            // remove all sections from this object
            // _data and _count properties are available since ZF 1.5
            $this->_data = array();
            $this->_count = 0;

            // merge all extracted sections with this object
            foreach ($data as $key => $item) {
                $this->merge($item);
            }
        }

        // nullify empty strings
        if (isset($options['nullifyEmpty']) && $options['nullifyEmpty']) {
            $this->_nullifyEmpty($this);
        }

        if (!$allowModifications) {
            $this->setReadOnly();
        }
    }

    /**
     * Converts empty strings to NULL values.
     *
     * @param Zend_Config $config
     * @throws Zend_Config_Exception    if config is read only
     */
    protected function _nullifyEmpty(Zend_Config $config)
    {
        foreach ($config as $key => $item) {
            if ($item === '') {
                $config->$key = null;
            } else if ($config instanceof $item) {
                $this->_nullifyEmpty($item);
            }
        }
    }
}
