<?php

class Zefram_Filter_Decode extends Zefram_Filter_Encode
{
    public function filter($value)
    {
        return $this->_adapter->decode($value);
    }
}
