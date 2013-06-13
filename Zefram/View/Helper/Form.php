<?php

/**
 * @version 2013-06-13
 */
class Zefram_View_Helper_Form extends Zend_View_Helper_Form
{
   /**
     * Render HTML form
     *
     * @param  string $form             Form name
     * @param  null|array $attribs      HTML form attributes
     * @param  false|string $content    Form content
     * @return string
     */
    public function form($name = null, $attribs = null, $content = false)
    {
        // return helper only if it was called with no arguments. Checking for
        // null === $name is insufficient, as null is a valid form name value.
        if (0 == func_num_args()) {
            return $this;
        }

        return parent::form($name, $attribs, $content);
    }

    /**
     * @param Zend_Form|array $form
     * @param array $attribs
     * @return string
     */
    public function openTag($form = null, $attribs = null)
    {
        if ($form instanceof Zend_Form) {
            $attribs = array_merge(
                array(
                    'method'  => $form->getMethod(),
                    'action'  => $form->getAction(),
                    'enctype' => $form->getEnctype(),
                    'id'      => $form->getId(),
                ), 
                $form->getAttribs(),
                (array) $attribs
            );
        } else {
            $attribs = (array) $form;

            // action attribute is required by XHTML 4.01 Strict doctype
            if (!isset($attribs['action'])) {
                $attribs['action'] = '';
            }
        }

        if (empty($attribs['id'])) {
            unset($attribs['id']);
        }

        $xhtml = '<form'
               . $this->_htmlAttribs($attribs)
               . '>';

        return $xhtml;
    }

    /**
     * @return string
     */
    public function closeTag()
    {
        return '</form>';
    }
}
