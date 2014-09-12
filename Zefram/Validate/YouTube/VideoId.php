<?php

/**
 * @package Zefram_Validate
 * @uses    Zend_Gdata
 */
class Zefram_Validate_YouTube_VideoId extends Zend_Validate_Abstract
{
    const INVALID_FORMAT  = 'invalidFormat';
    const VIDEO_NOT_FOUND = 'videoNotFound';

    /**
     * @var array
     */
    protected $_messageTemplates = array(
        self::INVALID_FORMAT  => 'Invalid YouTube video ID format',
        self::VIDEO_NOT_FOUND => 'The YouTube video ID is invalid or the video was deleted',
    );

    /**
     * @var array
     */
    protected $_messageVariables = array(
        'video' => '_video',
    );

    /**
     * @var Zend_Gdata_YouTube_VideoEntry
     */
    protected $_video;

    /**
     * @param  string $value
     * @return bool
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        if (!preg_match('/^[-_0-9a-z]{11,}$/i', $value)) {
            $this->_error(self::INVALID_FORMAT);
            return false;
        }

        try {
            $youtube = new Zend_Gdata_YouTube();
            $this->_video = $youtube->getVideoEntry($value);

        } catch (Zend_Gdata_App_HttpException $e) {
            $this->_error(self::VIDEO_NOT_FOUND);
            return false;
        }

        return true;
    }
}
