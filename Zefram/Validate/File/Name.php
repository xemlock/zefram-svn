<?php

/**
 * File name validation. Based on:
 * http://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
 *
 * @version 2014-03-06
 */
class Zefram_Validate_File_Name extends Zend_Validate_Abstract
{
    const ILLEGAL_CHARS = 'illegalChars';
    const TOO_LONG      = 'tooLong';

    protected $_messageTemplates = array(
        self::ILLEGAL_CHARS => 'File name contains one or more illegal characters: %illegalChars%',
        self::TOO_LONG      => 'File name length must not exceed %maxLength% characters',
    );

    protected $_messageVariables = array(
        'illegalChars' => '_illegalChars',
        'maxLength'    => '_maxLength',
    );

    /**
     * @var int
     */
    protected $_maxLength = 255;

    /**
     * @var string
     */
    protected $_illegalChars = '\\ / : * ? " < > |';

    /**
     * @var string
     */
    protected $_illegalCharsRegex = '#[\\\/:*?"<>|]#';

    /**
     * @param  string $value
     * @param  array  $file  File data from Zend_File_Transfer
     * @return bool
     */
    public function isValid($value, $file = null)
    {
        // if file transfer data was provided, use 'name' instead of provided
        // value, the latter is expected to equal 'tmp_name'
        if (isset($file['name'])) {
            $value = $file['name'];
        }

        $valid = true;

        if (function_exists('mb_strlen')) {
            $length = mb_strlen($value);
        } else {
            $length = strlen($value);
        }

        if ($length >= $this->_maxLength) {
            $this->_error(self::TOO_LONG);
            $valid = false;
        }

        if (preg_match($this->_illegalCharsRegex, $value)) {
            $this->_error(self::ILLEGAL_CHARS);
            $valid = false;
        }

        return $valid;
    }
}
