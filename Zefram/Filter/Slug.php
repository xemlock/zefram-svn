<?php

/**
 * Filter for creating SEO friendly slugs.
 *
 * @version 2014-07-31
 * @author  xemlock
 */ 
class Zefram_Filter_Slug extends Zefram_Filter_Translit
{
    public function filter($value)
    {
        $value = parent::filter($value);

        $value = trim($value, " -_\r\n\v\f");
        $value = preg_replace(array(
            '/[^-0-9a-z]/i',
            '/-+/',
        ), '-', $value);
        $value = strtolower($value);

        return $value;
    }
}
