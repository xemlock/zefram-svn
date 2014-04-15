<?php

class Zefram_View_Helper_Truncate extends Zend_View_Helper_Abstract
{
    /**
     * @param  string $string
     * @param  int $length
     * @param  string $postfix
     * @param  bool $breakWords
     * @return string
     */
    public function truncate($string, $length = 80, $postfix = '...', $breakWords = false)
    {
        return Zefram_Filter_StringTruncate::truncate(
            $string, $length, $postfix, $breakWords, $this->view->getEncoding()
        );
    }
}
