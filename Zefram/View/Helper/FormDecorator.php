<?php

class Zefram_View_Helper_FormDecorator extends Zend_View_Helper_Abstract
{
    protected $_element;

    /**
     * Set element to operate on.
     *
     * @param  Zend_Form_Element|Zend_Form|Zend_Form_DisplayGroup $element
     * @return Zefram_View_Helper_FormDecorator
     */
    public function setElement($element)
    {
        if ((!$element instanceof Zend_Form_Element)
            && (!$element instanceof Zend_Form)
            && (!$element instanceof Zend_Form_DisplayGroup))
        {
            throw new InvalidArgumentException(
                'Element is expected to be an instance of Zend_Form_Element, Zend_Form or Zend_Form_DisplayGroup'
            );
        }
        $this->_element = $element;
        return $this;
    }

    public function getElement()
    {
        return $this->_element;
    }

    /**
     * Render arbitrary decorator on a given element.
     *
     * @param  Zend_Form_Decorator_Interface|string $decorator
     * @param  array|string $options OPTIONAL
     * @param  array $content OPTIONAL
     * @return string
     */
    public function renderDecorator($decorator, $options = null, $content = null)
    {
        $element = $this->getElement();

        if (!$decorator instanceof Zend_Form_Decorator_Interface) {
            $decoratorName = (string) $decorator;
            $decorator = $element->getDecorator($decoratorName);

            if (empty($decorator)) {
                $decoratorClass = $element->getPluginLoader(Zend_Form::DECORATOR)->load($decoratorName);
                $decorator = new $decoratorClass;
            }
        }

        $decorator->setElement($element);

        if (is_string($options)) {
            $content = $options;
            $options = null;
        }

        if (is_array($options)) {
            $origOptions = $decorator->getOptions();
            $decorator->setOptions($options);
        }

        $output = $decorator->render($content);

        if (isset($origOptions)) {
            $decorator->setOptions($origOptions);
        }

        return $output;
    }

    public function __call($method, $args)
    {
        array_unshift($args, $method);
        return call_user_func_array(array($this, 'renderDecorator'), $args);
    }

    public function formDecorator($element)
    {
        return $this->setElement($element);
    }
}
