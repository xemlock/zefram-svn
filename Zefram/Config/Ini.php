<?php

/**
 * Ini config, with additional support for hierarchical structures 
 * stored using section names.
 *
 * @category   Zefram
 * @package    Zefram_Config
 * @copyright  Copyright (c) 2011 xemlock
 * @version    2011-07-26
 */
class Zefram_Config_Ini extends Zend_Config_Ini
{
    public function __construct($filename, $section = null, $options = false) 
    {
        // allow config modifications during initialisation
        if (is_bool($options)) {
            $allowModifications = $options;
            $options = true;
        } else {
            $options = (array) $options;
            $allowModifications = isset($options['allowModifications']) && $options['allowModifications'];
            $options['allowModifications'] = true;
        }

        parent::__construct($filename, $section, $options);

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

        if (!$allowModifications) {
            $this->setReadOnly();
        }
    }
}
