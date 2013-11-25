<?php

/**
 * cURL based file downloader.
 *
 * @uses     Zefram_Os
 * @uses     Zefram_Url
 * @uses     Zefram_Validate
 * @author   xemlock
 * @version  2013-11-25
 */
class Zefram_File_Download
{
    protected $_url;
    protected $_validators;
    protected $_destination;
    protected $_userAgent;

    protected $_readLimit = 0;
    protected $_readBytes;
    protected $_detectedFilename;

    protected $_fileInfo;
    protected $_outputStream;
    protected $_isValid = false;

    /**
     * @param  string $url
     * @throws Zend_Uri_Exception
     */
    public function __construct($url, array $options = null) // {{{
    {
        if (!extension_loaded('curl')) {
            throw new DomainException(
                'cURL extension is not available'
            );
        }

        $url = Zefram_Url::fromString($url);

        if (!$url->valid()) {
            throw new InvalidArgumentException(
                'Invalid URL supplied'
            );
        }

        if (!in_array($url->getScheme(), array('http', 'https', 'ftp', 'ftps'), true)) {
            throw new InvalidArgumentException(
                'Only HTTP(s) and FTP schemes are supported'
            );
        }

        $this->_url = $url->getUri();
        $this->_validators = new Zefram_Validate;
    } // }}}

    public function setDestination($dir) // {{{
    {
        $dir = realpath($dir);

        if (!is_dir($dir)) {
            throw new InvalidArgumentException(
                'The given destination is not a directory or does not exist'
            );
        }

        if (!is_writable($dir)) {
            throw new InvalidArgumentException(
                'The given destination is not writable'
            );
        }

        $this->_destination = $dir;
        return $this;
    } // }}}

    public function getDestination() // {{{
    {
        if (null === $this->_destination) {
            $this->setDestination($this->_getTempDir());
        }
        return $this->_destination;
    } // }}}

    protected function _getTempDir() // {{{
    {
        // Returns absolute path to a temporary directory. tempnam() requires
        // an absolute path otherwise it will use the system temp
        if (!($dir = Zefram_Os::getTempDir())) {
            throw new Exception('Temporary directory not found');
        }
        return $dir;
    } // }}}

    public function setValidators(array $validators) // {{{
    {
        $this->_validators->setValidators($validators);
        return $this;
    } // }}}

    public function addValidator($validator, $breakChainOnFailure = null, array $options = array()) // {{{
    {
        $this->_validators->addValidator($validator, $breakChainOnFailure, $options);
        return $this;
    } // }}}

    public function isDownloaded() // {{{
    {
        return (bool) $this->_fileInfo;
    } // }}}

    /**
     * Sets the maximum number of bytes read from remote file. May be useful
     * when enforcing file size limit, without the need of downloading whole
     * file.
     *
     * @param int $limit
     */
    public function setReadLimit($limit) // {{{
    {
        $limit = intval($limit);
        if ($limit < 0) {
            throw new InvalidArgumentException('Read limit must be equal to or greater than 0');
        }
        $this->_readLimit = $limit;
        return $this;
    } // }}}

    public function getReadLimit() // {{{
    {
        return $this->_readLimit;
    } // }}}

    public function setUserAgent($userAgent) // {{{
    {
        $this->_userAgent = (string) $userAgent;
        return $this;
    } // }}}

    public function getUserAgent() // {{{
    {
        return $this->_userAgent;
    } // }}}

    /**
     * Yep. This function is public, but should be considered internal, hence
     * the underscore.
     *
     * @param resource $curl
     * @param string $data
     */
    public function _write($curl, $data) // {{{
    {
        if (0 < ($limit = $this->getReadLimit())) {
            if ($this->_readBytes + strlen($data) >= $limit) {
                // append only exact number of bytes
                fwrite($this->_outputStream, substr($data, 0, $limit - $this->_readBytes));
                return -1;
            }
        }
        fwrite($this->_outputStream, $data);
        $this->_readBytes += strlen($data);
        return strlen($data);
    } // }}}

    public function _header($curl, $data) // {{{
    {
        $regex = "/Content-Disposition:\\s+attachment;\\s+filename=([^\r\n]+)/i";
        if (preg_match($regex, $data, $match)) {
            $this->_detectedFilename = trim($match[1], '";');
        }
        return strlen($data);
    } // }}}

    public function getFileInfo() // {{{
    {
        return $this->_fileInfo;
    } // }}}

    /**
     * @return bool
     */
    public function download() // {{{
    {
        if (null !== $this->_fileInfo) {
            return $this->_isValid;
        }

        $path = tempnam($this->getDestination(), '');
        $cookies = tempnam($this->_getTempDir(), '');

        $options = array(
            CURLOPT_URL            => $this->_url,
            CURLOPT_HEADER         => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_COOKIEFILE     => $cookies,
            CURLOPT_HEADERFUNCTION => array($this, '_header'),
            CURLOPT_WRITEFUNCTION  => array($this, '_write'),
        );

        if (($user_agent = $this->getUserAgent())) {
            $options[CURLOPT_USERAGENT] = $user_agent;
        }

        $curl = curl_init();
        curl_setopt_array($curl, $options);

        $this->_fileInfo = null;
        $this->_outputStream = fopen($path, 'w');

        $this->_detectedFilename = null;
        $this->_readBytes = 0;

        curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        if (is_file($cookies)) {
            unlink($cookies);
        }

        fclose($this->_outputStream);
        $this->_outputStream = null;

        if ($this->_readBytes) {
            if ($this->_detectedFilename) {
                $name = $this->_detectedFilename;
            } else {
                try {
                    $url = Zefram_Url::fromString($info['url']);
                    $name = basename($url->getPath());
                } catch (Exception $e) {
                    $name = basename($path);
                }
            }
            $this->_fileInfo = array(
                'name' => $name,
                'type' => $info['content_type'],
                'size' => $this->_readBytes,
                'tmp_name' => $path,
            );
        }

        return $this->_validate();
    } // }}}

    protected function _validate() // {{{
    {
        if (empty($this->_fileInfo)) {
            $valid = false;
        } else {
            $valid = $this->_validators->isValid($this->_fileInfo['tmp_name']);
        }
        $this->_isValid = $valid;
        return $valid;
    } // }}}

    public function isValid() // {{{
    {
        return $this->_isValid;
    } // }}}

    public function getMessages() // {{{
    {
        return $this->_validators->getMessages();
    } // }}}
}
