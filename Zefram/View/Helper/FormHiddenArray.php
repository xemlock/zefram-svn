<?php

class Zefram_View_Helper_FormHiddenArray extends Zend_View_Helper_FormElement
{
    /**
     * @return string
     */
    public function formHiddenArray($name, $value = null, array $attribs = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);
        extract($info); // name, id, value, attribs, options, listsep, disable, escape

        $xhtml = '';
        foreach ((array) $value as $key => $val) {
            $xhtml .= $this->_hidden($name, $val, $attribs);
        }

        return $xhtml;
    }
}
