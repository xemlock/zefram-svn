<?php

interface Zefram_Filter_Encode_Interface
{
    public function encode($value);

    public function decode($value);

    public function toString();
}
