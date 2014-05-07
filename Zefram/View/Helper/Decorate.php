<?php

class Zefram_View_Helper_Decorate extends Zend_View_Helper_Abstract
{
    /**
     * Render arbitrary decorator on a given element.
     *
     * @param  Zend_Form_Element|Zend_Form|Zend_Form_DisplayGroup $element
     * @param  Zend_Form_Decorator_Interface|string $decorator
     * @param  array|string $options OPTIONAL
     * @param  array $content OPTIONAL
     * @return string
     */
    public function decorate($element, $decorator, $options = null, $content = null)
    {
        if ((!$element instanceof Zend_Form_Element)
            && (!$element instanceof Zend_Form)
            && (!$element instanceof Zend_Form_DisplayGroup))
        {
            throw new InvalidArgumentException(
                'Element is expected to be an instance of Zend_Form_Element, Zend_Form or Zend_Form_DisplayGroup'
            );
        }

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
}
