<?php

/**
 * Short words token filter.
 */
abstract class Zefram_Search_Lucene_Analysis_TokenFilter
    extends Zend_Search_Lucene_Analysis_TokenFilter
{
    public function __construct(array $options = null)
    {
        if ($options) {
            $this->setOptions($options);
        }
    }

    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . $key;
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
        return $this;
    }
}
