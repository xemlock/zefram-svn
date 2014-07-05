<?php

/**
 * @category   Zefram
 * @package    Zefram_View
 * @subpackage Helper
 * @author     xemlock
 * @version    2014-07-05
 */
class Zefram_View_Helper_FileSize extends Zend_View_Helper_Abstract
{
    const MODE_TRADITIONAL = 'traditional';     // 1 KB = 2^10 B
    const MODE_SI          = 'si';              // 1 KB = 10^3 B
    const MODE_IEC         = 'iec';             // 1 KiB = 2^10 B

    /**
     * @param  int $bytes
     * @param  int $precision OPTIONAL
     * @param  string $mode OPTIONAL
     * @return string
     */
    public function fileSize($bytes, $precision = 0, $mode = self::MODE_TRADITIONAL)
    {
        $bytes = floor($bytes);
        $radix = 1024;
        $iec = false;

        switch ($mode) {
            case self::MODE_TRADITIONAL:
                break;

            case self::MODE_IEC:
                $iec = true;
                break;

            case self::MODE_SI:
                $radix = 1000;
                break;

            default:
                throw new Zend_View_Helper_Exception("Invalid mode: '$mode'");
        }

        if ($iec) {
            $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
        } else {
            $units = array('B', 'KB',  'MB',  'GB',  'TB',  'PB',  'EB',  'ZB',  'YB');
        }

        $idx = 0;
        $end = count($units) - 1;

        while ($bytes > $radix) {
            $bytes /= $radix;
            if ($idx == $end) {
                break;
            }
            ++$idx;
        }

        $fileSize = round($bytes, $precision);

        try {
            $locale = $this->view->translate()->getLocale();
            $fileSize = Zend_Locale_Format::getFloat($fileSize, array('locale' => $locale));
        } catch (Zend_View_Exception $e) {
        }

        return $fileSize . ' ' . $units[$idx];
    }
}
