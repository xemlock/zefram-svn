<?php

// code for storing entries and centeral directory contents taken from
// Contao ZipWriter
class Zefram_File_Archive_ZipWriter
{
    const CREATE    = 0x0001;
    const EXCL      = 0x0002;
    const CHECKCONS = 0x0004;
    const OVERWRITE = 0x0008;

    /**
     * Local file header signature
     * @var string
     */
    const FILE_SIGNATURE = "\x50\x4b\x03\x04";

    /**
     * Central directory file header signature
     * @var string
     */
    const CENTRAL_DIR_START = "\x50\x4b\x01\x02";

    /**
     * End of central directory signature
     * @var string
     */
    const CENTRAL_DIR_END = "\x50\x4b\x05\x06";

    /**
     * File handle
     * @var resource
     */
    protected $_handle;

    /**
     * Central directory
     * @var string
     */
    protected $_centralDir;

    /**
     * @var array
     */
    protected $_files = array();

    /**
     * File name
     * @var resource
     */
    protected $strFile;

    /**
     * Temporary name
     * @var resource
     */
    protected $strTemp;

    public function __get($key)
    {
        switch ($key) {
            case 'numFiles':
                return count($this->_files);
        }

        return null;
    }

    /**
     * Create a new zip archive
     *
     * @param string $strFile The file path
     *
     * @throws \Exception If the temporary file cannot be created or opened
     */
    public function open($file, $flags = 0)
    {
        $this->_closeHandles();
        $this->_handle = @fopen($file, 'wb');
        return (bool) $this->_handle;
    }

    /**
     * Close the file handle if it has not been done yet
     */
    public function __destruct()
    {
        $this->_closeHandles();
    }

    protected function _name($name)
    {
        // ZipArchive does not care about Windows' or absolute paths,
        // why should we?
        $name = str_replace('\\', '/', $name);
        $name = rtrim($name, '/');

        $parts = explode('/', $name);
        $name = array();

        while (null !== ($part = array_shift($parts))) {
            switch ($part) {
                case '':
                case '.':
                    break;

                case '..':
                    array_pop($name);
                    break;

                default:
                    $name[] = $part;
                    break;
            }
        }

        $name = join('/', $name);
        return $name;
    }

    public function addEmptyDir($name)
    {
        $this->_files[] = array(
            'handle' => '',
            'name'   => $this->_name($name) . '/',
            'mtime'  => time(),
        );
        return true;
    }

    public function addFile($path, $name = null)
    {
        if (!($fileHandle = @fopen($path, 'rb'))) {
            return false;
        }

        if (strlen($name) === 0) {
            $name = $path;
        }

        $this->_files[] = array(
            'handle' => $fileHandle,
            'name'   => $this->_name($name),
            'mtime'  => filemtime($path),
        );
        return true;
    }

    public function addFromString($name, $contents)
    {
        $this->_files[] = array(
            'handle' => (string) $contents,
            'name'   => $this->_name($name),
            'mtime'  => time(),
        );
        return true;
    }

    protected function _writeStream($handle, $name, $mtime)
    {
        if (is_string($handle)) {
            $data = $handle;
        } else {
            $data = stream_get_contents($handle);
        }

        $sizeUncompressed = strlen($data);
        $deflate = $sizeUncompressed > 0;
        $crc32 = crc32($data);

        // strip last 4 and first 2 bytes of gzcompress() output
        // First 2 bytes are CMG and FLG bytes respectively, last 4 bytes
        // contain an Adler-32 checksum, see: http://tools.ietf.org/html/rfc1950
        if ($deflate) {
            $data = gzcompress($data);
            $data = substr($data, 2, -4);
        }

        $sizeCompressed = strlen($data);

        $entry['file_signature']            = self::FILE_SIGNATURE;
        $entry['version_needed_to_extract'] = "\x14\x00";
        $entry['general_purpose_bit_flag']  = "\x00\x00";
        $entry['compression_method']        = $deflate ? "\x08\x00" : "\x00\x00";
        $entry['last_mod_file_hex']         = $this->_timeToHex($mtime);
        $entry['crc-32']                     = pack('V', $crc32);
        $entry['compressed_size']           = pack('V', $sizeCompressed);
        $entry['uncompressed_size']         = pack('V', $sizeUncompressed);
        $entry['file_name_length']          = pack('v', strlen($name));
        $entry['extra_field_length']        = "\x00\x00";
        $entry['file_name']                 = $name;
        $entry['extra_field']               = '';

        // Store file offset in the output file
        $offset = ftell($this->_handle);

        // Write file contents
        fputs($this->_handle, implode('', $entry));
        fputs($this->_handle, $data);

        // Write corresponding entry in central directory
        $header['header_signature']          = self::CENTRAL_DIR_START;
        $header['version_made_by']           = "\x00\x00";
        $header['version_needed_to_extract'] = $entry['version_needed_to_extract'];
        $header['general_purpose_bit_flag']  = $entry['general_purpose_bit_flag'];
        $header['compression_method']        = $entry['compression_method'];
        $header['last_mod_file_hex']         = $entry['last_mod_file_hex'];
        $header['crc-32']                    = $entry['crc-32'];
        $header['compressed_size']           = $entry['compressed_size'];
        $header['uncompressed_size']         = $entry['uncompressed_size'];
        $header['file_name_length']          = $entry['file_name_length'];
        $header['extra_field_length']        = $entry['extra_field_length'];
        $header['file_comment_length']       = "\x00\x00";
        $header['disk_number_start']         = "\x00\x00";
        $header['internal_file_attributes']  = "\x00\x00";
        $header['external_file_attributes']  = pack('V', 32);
        $header['offset_of_local_header']    = pack('V', $offset);
        $header['file_name']                 = $entry['file_name'];
        $header['extra_field']               = $entry['extra_field'];
        $header['file_comment']              = '';

        // Add entry to central directory
        $this->_centralDir .= implode('', $header);
    }

    protected function _write()
    {
        foreach ($this->_files as $file) {
            $this->_writeStream($file['handle'], $file['name'], $file['mtime']);
        }

        // Add end of central directory record
        $entry['archive_signature']        = self::CENTRAL_DIR_END;
        $entry['number_of_this_disk']      = "\x00\x00";
        $entry['number_of_disk_with_cd']   = "\x00\x00";
        $entry['total_cd_entries_on_disk'] = pack('v', count($this->_files));
        $entry['total_cd_entries']         = pack('v', count($this->_files));
        $entry['size_of_cd']               = pack('V', strlen($this->_centralDir));
        $entry['offset_start_cd']          = pack('V', ftell($this->_handle));
        $entry['comment_length']           = "\x00\x00";
        $entry['comment']                  = '';

        // Write central directory and end record (in this order)
        fputs($this->_handle, $this->_centralDir);
        fputs($this->_handle, implode('', $entry));

        $this->_closeHandles();

        return true;
    }

    protected function _closeHandles()
    {
        if ($this->_handle) {
            fclose($this->_handle);
            $this->_handle = null;
        }

        foreach ($this->_files as $file) {
            if (is_resource($file['handle'])) {
                fclose($file['handle']);
            }
        }
        $this->_files = array();
    }

    /**
     * Write the central directory and close the file handle
     */
    public function close()
    {
        return $this->_write();
    }

    /**
     * Convert a Unix timestamp to a hexadecimal value
     *
     * @param  int $time The Unix timestamp
     * @return int The hexadecimal value
     */
    protected function _timeToHex($time)
    {
        if ($time === null) {
            return "\x00\x00\x00\x00";
        }

        $parts = $time ? getdate($time) : getdate();
        $hex = dechex(
            (($parts['year'] - 1980) << 25) |
             ($parts['mon'] << 21) |
             ($parts['mday'] << 16) |
             ($parts['hours'] << 11) |
             ($parts['minutes'] << 5) |
             ($parts['seconds'] >> 1)
        );
        return pack("H*", $hex[6] . $hex[7] . $hex[4] . $hex[5] . $hex[2] . $hex[3] . $hex[0] . $hex[1]);
    }
}
