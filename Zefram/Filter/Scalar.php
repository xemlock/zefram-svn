<?php

class Zefram_Filter_Scalar implements Zend_Filter_Interface
{
    public function filter($value)
    {
        if (is_scalar($value)) {
            return $value;
        }

        return '';
    }
}
