<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use UnexpectedValueException;

/**
 * Class LibFs - Low level file-system utility functions
 *
 * @covers \Ktomk\Pipelines\LibFs
 */
class LibFs
{
    /**
     * @param string $path
     * @param string $mode [optional]
     *
     * @return bool
     */
    public static function canFopen($path, $mode = null)
    {
        if (null === $mode) {
            $mode = 'rb';
        }

        $handle = @fopen($path, $mode);
        if (false === $handle) {
            return false;
        }

        /** @scrutinizer ignore-unhandled */
        @fclose($handle);

        return true;
    }

    /**
     * locate (readable) file by basename upward all parent directories
     *
     * @param string $basename
     * @param string $directory [optional] directory to operate from, defaults
     *               to "." (relative path of present working directory)
     *
     * @return null|string
     */
    public static function fileLookUp($basename, $directory = null)
    {
        if ('' === $directory || null === $directory) {
            $directory = '.';
        }

        for (
            $dirName = $directory, $old = null;
            $old !== $dirName;
            $old = $dirName, $dirName = dirname($dirName)
        ) {
            $test = $dirName . '/' . $basename;
            if (self::isReadableFile($test)) {
                return $test;
            }
        }

        return null;
    }

    /**
     * check if path is absolute
     *
     * @param string $path
     *
     * @return bool
     */
    public static function isAbsolutePath($path)
    {
        // TODO: a variant with PHP stream wrapper prefix support
        /* @see isStreamUri */

        $count = strspn($path, '/', 0, 3) % 2;

        return (bool)$count;
    }

    /**
     * check if path is basename
     *
     * @param string $path
     *
     * @return bool
     */
    public static function isBasename($path)
    {
        if (in_array($path, array('', '.', '..'), true)) {
            return false;
        }

        if (false !== strpos($path, '/')) {
            return false;
        }

        return true;
    }

    /**
     * see 2.2 Standards permit the exclusion of bad filenames / POSIX.1-2008
     *
     * @link https://dwheeler.com/essays/fixing-unix-linux-filenames.html
     *
     * @param string $filename
     *
     * @return bool
     */
    public static function isPortableFilename($filename)
    {
        # A-Z, a-z, 0-9, <period>, <underscore>, and <hyphen>)
        $result = preg_match('(^(?!-)[A-Za-z0-9._-]+$)', $filename);
        if (false === $result) {
            // @codeCoverageIgnoreStart
            throw new UnexpectedValueException('preg_match pattern failed');
            // @codeCoverageIgnoreEnd
        }

        return 1 === $result;
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public static function isReadableFile($path)
    {
        if (!is_file($path)) {
            return false;
        }

        return is_readable($path) ?: self::canFopen($path, 'rb');
    }

    /**
     * create directory if not yet exists
     *
     * @param string $path
     * @param int $mode [optional]
     *
     * @return string
     */
    public static function mkDir($path, $mode = 0777)
    {
        if (!is_dir($path)) {
            /** @noinspection NestedPositiveIfStatementsInspection */
            if (!mkdir($path, $mode, true) && !is_dir($path)) {
                // @codeCoverageIgnoreStart
                throw new \RuntimeException(
                    sprintf('Directory "%s" was not created', $path)
                );
                // @codeCoverageIgnoreEnd
            }
        }

        return $path;
    }

    /**
     * Normalize a path as/if common in PHP
     *
     * E.g. w/ phar:// in front which means w/ stream wrappers in
     * mind.
     *
     * @param string $path
     *
     * @return string
     */
    public static function normalizePath($path)
    {
        $buffer = $path;

        $scheme = '';
        // TODO support for all supported stream wrappers (w/ absolute/relative notation?)
        /* @see isStreamUri */
        /* @see isAbsolutePath */
        if (0 === strpos($buffer, 'phar://') || 0 === strpos($buffer, 'file://')) {
            $scheme = substr($buffer, 0, 7);
            $buffer = substr($buffer, 7);
        }

        $normalized = self::normalizePathSegments($buffer);

        return $scheme . $normalized;
    }

    /**
     * Resolve relative path segments in a path on it's own
     *
     * This is not realpath, not resolving any links.
     *
     * @param string $path
     *
     * @return string
     */
    public static function normalizePathSegments($path)
    {
        if ('' === $path) {
            return $path;
        }

        $buffer = $path;

        $prefix = '';
        $len = strspn($buffer, '/');
        if (0 < $len) {
            $prefix = substr($buffer, 0, $len);
            $buffer = substr($buffer, $len);
        }

        $buffer = rtrim($buffer, '/');

        if (in_array($buffer, array('', '.'), true)) {
            return $prefix;
        }

        $pos = strpos($buffer, '/');
        if (false === $pos) {
            return $prefix . $buffer;
        }

        $buffer = preg_replace('~/+~', '/', $buffer);

        $segments = explode('/', $buffer);
        $stack = array();
        foreach ($segments as $segment) {
            $i = count($stack) - 1;
            if ('.' === $segment) {
                continue;
            }

            if ('..' !== $segment) {
                $stack[] = $segment;

                continue;
            }

            if (($i > -1) && '..' !== $stack[$i]) {
                array_pop($stack);

                continue;
            }

            $stack[] = $segment;
        }

        return $prefix . implode('/', $stack);
    }

    /**
     * rename a file
     *
     * @param string $old
     * @param string $new
     *
     * @return string new file-name
     */
    public static function rename($old, $new)
    {
        if (!@rename($old, $new)) {
            throw new \RuntimeException(sprintf('Failed to rename "%s" to "%s"', $old, $new));
        }

        return $new;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    public static function rm($file)
    {
        if (self::isReadableFile($file)) {
            unlink($file);
        }

        return $file;
    }

    /**
     * @param string $dir
     *
     * @throws UnexpectedValueException
     *
     * @return void
     */
    public static function rmDir($dir)
    {
        $result = @lstat($dir);
        if (false === $result) {
            return;
        }

        $dirs = array();
        $dirs[] = $dir;
        for ($i = 0; isset($dirs[$i]); $i++) {
            $current = $dirs[$i];
            $result = @scandir($current);
            if (false === $result) {
                throw new UnexpectedValueException(sprintf('Failed to open directory: %s', $current));
            }
            $files = array_diff($result, array('.', '..'));
            foreach ($files as $file) {
                $path = "${current}/${file}";
                if (is_dir($path)) {
                    $dirs[] = $path;
                } elseif (is_file($path)) {
                    self::rm($path);
                }
            }
        }

        while (null !== ($pop = array_pop($dirs))) {
            /* @scrutinizer ignore-unhandled */
            @rmdir($pop);
        }
    }

    /**
     * create symbolic link, recreate if it exists
     *
     * @param string $target
     * @param string $link
     *
     * @return void
     */
    public static function symlink($target, $link)
    {
        self::unlink($link);
        symlink($target, $link);
    }

    /**
     * @param string $link
     *
     * @return void
     */
    public static function unlink($link)
    {
        if (is_link($link)) {
            unlink($link);
        }
    }
}
