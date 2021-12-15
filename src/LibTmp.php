<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use UnexpectedValueException;

/**
 * Library for temporary files and directories related functions
 */
class LibTmp
{
    /**
     * Create temporary file w/ contents
     *
     * @param string $buffer
     *
     * @return string path of temporary file
     */
    public static function tmpFilePut($buffer)
    {
        list(, $path) = self::tmpFile();
        file_put_contents($path, $buffer);

        return $path;
    }

    /**
     * Create handle and path of a temporary file (which gets cleaned up)
     *
     * @return array [resource stream-handle, string path]
     */
    public static function tmpFile()
    {
        $handle = tmpfile();
        if (false === $handle) {
            // @codeCoverageIgnoreStart
            throw new UnexpectedValueException('Unable to create temporary file');
            // @codeCoverageIgnoreEnd
        }
        $meta = stream_get_meta_data($handle);

        return array($handle, $meta['uri']);
    }

    /**
     * Create temporary directory (which does not get cleaned up)
     *
     * @param string $prefix [optional]
     *
     * @return string path
     */
    public static function tmpDir($prefix = '')
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);
        LibFs::rm($path);
        LibFs::mkDir($path, 0700);

        return $path;
    }
}
