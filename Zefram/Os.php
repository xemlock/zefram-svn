<?php

class Zefram_Os 
{
    public function normalizePath($path)
    {
        $parts = preg_split('/[\\\/][\\\/]*/', $path);
        $normalized = array();

        while ($parts) {
            $part = array_shift($parts);

            switch ($part) {
                case '..':
                    $atroot = empty($normalized)
                              || (1 == count($normalized) && ($normalized[0] == '' || substr($normalized[0], -1) == ':'));
                    if (!$atroot) {
                        array_pop($normalized);
                    }
                    break;

                case '.':
                    break;

                case '':
                    if (empty($normalized)) {
                        array_push($normalized, '');
                    }
                    break;

                default:
                    array_push($normalized, $part);
                    break;
            }
        }

        return implode('/', $normalized);
    }

    public static function pathLookup($filename)
    {
        $dirs = explode(PATH_SEPARATOR, getenv('PATH'));
        array_unshift($dirs, getcwd());
        foreach ($dirs as $dir) {
            $path = $dir . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($path)) {
                return $path;
            }
        }
        return false;
    }

    public static function isWindows()
    {
        static $isWindows = null;
        if (null === $isWindows) {
            $isWindows = strpos(strtoupper(PHP_OS), "WIN") !== false;
        }
        return $isWindows;
    }

    public static function exec($command)
    {
        // From php.net forum:
        //  In Windows, exec() issues an internal call to "cmd /c your_command".
        //  This implies that your command must follow the rules imposed by 
        //  cmd.exe which includes an extra set of quotes around the full 
        //  command (see: http://ss64.com/nt/cmd.html).
        //  Current PHP versions take this into account and add the quotes 
        //  automatically, but old versions didn't. Apparently, the change was 
        //  made in PHP/5.3.0 yet not backported to 5.2.x because it's 
        //  a backwards incompatible change.
        if (self::_isWindows() && version_compare(PHP_VERSION, '5.3.0') < 0) {
            $cmd = "\"$cmd\"";
        }
        return shell_exec($cmd);
    }
}
