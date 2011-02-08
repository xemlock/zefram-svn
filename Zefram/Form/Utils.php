<?php

abstract class Zefram_Form_Utils
{
    static public function formDecorators() 
    {
        // <markupListStart>
        //   <markupListItemStart>
        //   <markupListItemEnd>
        // <markupListEnd>
        return array(
            new Zend_Form_Decorator_FormErrors(array(
                'onlyCustomFormErrors' => true,
                'markupListStart'     => '<div class="form-errors">',
                'markupListEnd'       => '</div>',
                'markupListItemStart' => '',
                'markupListItemEnd'   => '',
            )),
            'FormElements',
            array(
                'HtmlTag', 
                array('tag' => 'div', 'class' => 'form')
            ),
            'Form',
        );      
    }

    static public function elementDecorators()
    {
        return array(
            'ViewHelper',
            'Description',
            'Errors',
            array(
                array('data' => 'HtmlTag'),
                array('tag' => 'dd')
            ),
            array(
                'Label', 
                array('tag' => 'dt')
            ),
            array(
              array('row' => 'HtmlTag'),
              array('tag' => 'dl')
            ),
        );    
    }

    static public function hiddenDecorators() 
    {
        return array(
            'ViewHelper',
        );
    }
}
