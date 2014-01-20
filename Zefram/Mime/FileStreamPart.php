<?php

/**
 * MIME part implementation specialized in handling file streams. It overcomes
 * all obvious flaws of the Zend_Mime_Part class, which are:
 *
 *   1. Stream contents can be read only once, either encoded or raw. Once
 *      the raw content is read, encoded content is empty and vice versa;
 *   2. Stream can be encoded multiple times by the same filter;
 *
 * @version 2014-01-20
 * @author  xemlock
 */
class Zefram_Mime_FileStreamPart extends Zend_Mime_Part
{
    const FILTER_QUOTEDPRINTABLE = 'convert.quoted-printable-encode';

    const FILTER_BASE64 = 'convert.base64-encode';

    /**
     * Path to file
     * @var string
     */
    protected $_path;

    /**
     * Name of a currently registered stream filter
     * @var string
     */
    protected $_filterName;

    /**
     * @var resource
     */
    protected $_filter;

    /**
     * After construction instance is guaranteed to have id and filename
     * properties set.
     *
     * @param  string $path
     * @param  array $options OPTIONAL
     * @return void
     * @throws Zend_Mime_Exception
     */
    public function __construct($path, $options = null)
    {
        if (false === ($realPath = realpath($path))) {
            throw new Zend_Mime_Exception(sprintf(
                'Unable to locate file "%s"', $path
            ));
        }

        if (false === ($stream = @fopen($realPath, 'rb'))) {
            throw new Zend_Mime_Exception(sprintf(
                'Unable to open file stream for reading "%s"', $realPath
            ));
        }

        $this->_path = $realPath;

        $this->_content = $stream;
        $this->_isStream = true;

        $this->id = md5($realPath);
        $this->filename = basename($path); // use basename from the given path

        if (is_array($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        fclose($this->_content);
    }

    /**
     * @param  array $options
     * @return Zefram_Mime_FileStreamPart
     */
    public function setOptions(array $options)
    {
        $ref = new ReflectionClass($this);
        $props = $ref->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($props as $prop) {
            $propName = $prop->getName();
            if (isset($options[$propName])) {
                $this->{$propName} = $options[$propName];
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * @return string
     */
    public function getRawContent()
    {
        $this->resetStream();
        return parent::getRawContent();
    }

    /**
     * Sets the file position indicator to the beginning of the stream,
     * removes any previously registered stream filter.
     *
     * @return Zefram_Mime_FileStreamPart
     */
    public function resetStream()
    {
        if ($this->_filter) {
            stream_filter_remove($this->_filter);
            $this->_filterName = null;
            $this->_filter = null;
        }
        rewind($this->_content);
        return $this;
    }

    /**
     * Return a filtered stream for reading the file contents.
     *
     * @return stream
     * @throws Zend_Mime_Exception if filter cannot be appended to stream
     */
    public function getEncodedStream()
    {
        $this->resetStream();

        switch ($this->encoding) {
            case Zend_Mime::ENCODING_QUOTEDPRINTABLE:
                $filterName = self::FILTER_QUOTEDPRINTABLE;
                break;

            case Zend_Mime::ENCODING_BASE64:
                $filterName = self::FILTER_BASE64;
                break;

            default:
                $filterName = null;
                break;
        }

        if ($filterName) {
            $filter = stream_filter_append(
                $this->_content,
                $filterName,
                STREAM_FILTER_READ,
                array(
                    'line-length'      => 76,
                    'line-break-chars' => Zend_Mime::LINEEND
                )
            );

            if (!is_resource($filter)) {
                throw new Zend_Mime_Exception(sprintf(
                    'Failed to append filter "%s" to stream', $filterName
                ));
            }

            $this->_filter = $filter;
            $this->_filterName = $filterName;
        }

        return $this->_content;
    }
}
