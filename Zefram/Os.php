<?php

abstract class Zefram_Os 
{
    public static function normalizePath($path) // {{{
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
    } // }}}

    public static function pathLookup($filename, $path = null)
    {
        if (null === $path) {
            $path = getenv('PATH');
        }
        $dirs = explode(PATH_SEPARATOR, $path);
        array_unshift($dirs, getcwd());
        foreach ($dirs as $dir) {
            $path = $dir . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($path)) {
                return $path;
            }
        }
        return false;
    }

    public static function isWindows() // {{{
    {
        static $_isWindows = null;
        if (null === $_isWindows) {
            $_isWindows = stripos(PHP_OS, 'WIN') !== false;
        }
        return $_isWindows;
    } // }}}

    public static function exec($exec, $args = null) // {{{
    {
        if (is_array($args)) {
            // TODO
        }

        // From php.net forum:
        // In Windows, exec() issues an internal call to "cmd /c your_command".
        // This implies that your command must follow the rules imposed by 
        // cmd.exe which includes an extra set of quotes around the full 
        // command (see: http://ss64.com/nt/cmd.html).
        // Current PHP versions take this into account and add the quotes 
        // automatically, but old versions don't. Apparently, the change 
        // was made in PHP/5.3.0 yet not backported to 5.2.x because it's
        // a backwards incompatible change.
        if (self::isWindows()) {
            $ext = substr(strrchr(basename($exec), '.'), 1);
            if (0 == strlen($ext)) {
                $exec .= '.exe'; // is this really necessary?
            }
            $exec = escapeshellarg($exec);
            if (version_compare(PHP_VERSION, '5.3.0') < 0 && !strncmp($exec, '"', 1)) {
                $command = "\"$exec $args\"";
            } else {
                $command = "$exec $args";
            }
        } else {
            $command = "$exec $args";
        }
        return shell_exec($command);
    } // }}}

    public static function setEnv($key, $value) // {{{
    {
        // putenv/getenv and $_ENV are completely distinct environment stores
        $_ENV[$key] = $value;
        putenv("$key=$value");
    } // }}}

    /**
     * @return string|false
     */
    public static function getTempDir() // {{{
    {
        $tmpdir = array();

        if (function_exists('sys_get_temp_dir')) { // requires PHP 5.2.1
            $tmpdir[sys_get_temp_dir()] = true;
        }

        foreach (array('TMP', 'TEMP', 'TMPDIR') as $var) {
            $dir = realpath(getenv($var));
            if ($dir) {
                $tmpdir[$dir] = true;
            }
        }

        $tmpdir['/tmp'] = true;
        $tmpdir['\\temp'] = true;

        foreach (array_keys($tmpdir) as $dir) {
            if (is_dir($dir) && is_writable($dir)) {
                return $dir;
            }
        }

        $tempfile = tempnam(md5(uniqid(rand(), true)), '');
        if ($tempfile) {
            unlink($tempfile);
            return realpath(dirname($tempfile));
        }

        return false;
    } // }}}
}
