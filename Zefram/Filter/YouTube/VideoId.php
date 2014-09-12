<?php

class Zefram_Filter_YouTube_VideoId implements Zend_Filter_Interface
{
    const VIDEO_ID_REGEX = '[-_0-9A-Za-z]{11,}';

    /**
     * @param  string $value
     * @return string|false
     */
    public function filter($value)
    {
        if (preg_match('/^' . self::VIDEO_ID_REGEX . '$/', $value)) {
            return $value;
        }

        // try to extract video ID from URL
        preg_match('/(\\/(embed|v|vi|user)\\/|v=)(?P<video_id>' . self::VIDEO_ID_REGEX . ')/', $value, $match);
        if (isset($match['video_id'])) {
            return $match['video_id'];
        }

        return false;
    }
}
