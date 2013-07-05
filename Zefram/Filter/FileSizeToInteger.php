<?php

class Zefram_Filter_FileSizeToInteger implements Zend_Filter_Interface
{
    protected $_binary = true;

    /**
     * @param array|Zend_Config $options
     */
    public function __construct($options = null)
    {
        if (null !== $options) {
            if (is_object($options) && method_exists($options, 'toArray')) {
                $options = $options->toArray();
            }

            $options = (array) $options;

            if (isset($options['binary'])) {
                $this->setBinary($options['binary']);
            }
        }
    }

    /**
     * @param  bool $binary
     * @return Zefram_Filter_FileSizeToInteger
     */
    public function setBinary($binary)
    {
        $this->_binary = (bool) $binary;
        return $this;
    }

    /**
     * @return bool
     */
    public function getBinary()
    {
        return $this->_binary;
    }

    /**
     * @param  string $fileSize
     * @param  bool $binary
     * @return int
     */
    public function filter($fileSize, $binary = null)
    {
        if (!is_int($fileSize)) {
            // trim whitespaces and units
            $fileSize = trim($fileSize, " \t\n\rBb");

            if (!is_numeric($fileSize)) {
                $suffix = strtoupper(substr($fileSize, -1));
                $fileSize = intval(substr($fileSize, 0, -1));

                $binary = null === $binary ? $this->_binary : $binary;
                $multiplier = $binary ? 1024 : 1000;

                switch ($suffix) {
                    case 'Y':
                        $fileSize *= $multiplier; // intentional no break

                    case 'Z':
                        $fileSize *= $multiplier; // intentional no break

                    case 'E':
                        $fileSize *= $multiplier; // intentional no break

                    case 'P':
                        $fileSize *= $multiplier; // intentional no break

                    case 'T':
                        $fileSize *= $multiplier; // intentional no break

                    case 'G':
                        $fileSize *= $multiplier; // intentional no break

                    case 'M' :
                        $fileSize *= $multiplier; // intentional no break

                    case 'K' :
                        $fileSize *= $multiplier; // intentional no break

                    default :
                        break;
                }
            }
        }

        return intval($fileSize);
    }
}
