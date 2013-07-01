<?php

class Zefram_View_Helper_Translate extends Zend_View_Helper_Translate
{
    /**
     * Return translated plural form of message for given number.
     *
     * plural(plural0, plural1, ..., pluralN, number, locale)
     *
     * @param  string $messageId,...
     * @param  int $number OPTIONAL
     * @param  string|Zend_Locale $locale OPTIONAL
     * @return string|array
     *         array of plural forms if no number was given
     */
    public function plural($messageId)
    {
        $translate = $this->getTranslator();

        if (null === $translate) {
            return $messageId;
        }

        $args = func_get_args();
        array_shift($args);

        if (empty($args)) {
            return $translate->translate($messageId);
        }

        $locale = array_pop($args);

        if (is_numeric($locale)) {
            array_push($args, $locale);
            $locale = null;
        }

        array_unshift($args, $messageId);

        return $translate->translate($args, $locale);
    }
}
