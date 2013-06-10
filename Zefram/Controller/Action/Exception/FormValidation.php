<?php

class Zefram_Controller_Action_Exception_FormValidation extends Zefram_Controller_Action_Exception
{
    protected $elementName;

    /**
     * @param string $elementName
     * @param string $message
     * @param int $code OPTIONAL
     * @param Exception $previous OPTIONAL
     */
    public function __construct($elementName = '', $message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->elementName = (string) $elementName;
    }

    /**
     * @return string
     */
    public function getElementName()
    {
        return $this->elementName;
    }
}
