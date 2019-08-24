<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use UnexpectedValueException;

class Lib
{
    public static function r(&$v, $d)
    {
        if (isset($v)) {
            return $v;
        }

        return $d;
    }

    /**
     * @param mixed $v variable reference
     * @param mixed $d [optional]  default value (null)
     * @return void
     */
    public static function v(&$v, $d = null)
    {
        if (!isset($v)) {
            $v = $d;
        }
    }

    /**
     * @return string UUID version 4
     */
    public static function generateUuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    public static function cmd($command, array $arguments)
    {
        $buffer = $command;

        $arguments = call_user_func_array('self::merge', $arguments);

        foreach ($arguments as $argument) {
            $buffer .= ' ' . self::quoteArg($argument);
        }

        return $buffer;
    }

    /**
     * quote an argument to preserve its value verbatim when used as
     * a utility argument in shell
     *
     * @param string $argument
     * @return string
     */
    public static function quoteArg($argument)
    {
        $parts = explode("'", $argument);

        $buffer = '';
        foreach ($parts as $index => $part) {
            $index && $buffer .= "\\'";
            $safe = preg_match('~^[a-zA-Z0-9,._+@%/-]*$~', $part);
            $buffer .= $safe ? $part : "'${part}'";
        }

        if ('' === $buffer) {
            $buffer = "''";
        }

        return $buffer;
    }

    /**
     * Turn multi-line string into an array of lines.
     *
     * Handles no newline at the end of buffer
     *
     * @param string $buffer
     * @return array|string[]
     */
    public static function lines($buffer)
    {
        $lines = explode("\n", $buffer);
        if ($c = count($lines) and '' === $lines[$c - 1]) {
            array_pop($lines);
        }

        return $lines;
    }

    /**
     * merge n parameters, if a scalar, turned into array, otherwise must be an array
     */
    public static function merge()
    {
        if (!$arrays = func_get_args()) {
            return $arrays;
        }

        foreach ($arrays as $key => $value) {
            if (!is_array($value)) {
                $arrays[$key] = (array)$value;
            }
        }

        return call_user_func_array('array_merge', $arrays);
    }

    /**
     * Chunk an array of strings based on maximum string length per chunk
     *
     * @param array|string[] $array
     * @param $maxLength
     * @param int $overHeadPerEntry
     * @return array|array[]
     */
    public static function arrayChunkByStringLength(array $array, $maxLength, $overHeadPerEntry = 0)
    {
        $chunks = array();
        $chunkStringLength = 0;
        $chunk = array();

        foreach ($array as $key => $value) {
            $entryLen = strlen($value) + $overHeadPerEntry;
            if ($chunkStringLength + $entryLen > $maxLength) {
                if (empty($chunk)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'maximum length of %d is too little to chunk the array at %s %s (%d chunk(s) so far)',
                            $maxLength,
                            is_string($key) ? 'key' : 'index',
                            is_string($key) ? var_export($key, true) : (int)$key,
                            count($chunks)
                        )
                    );
                }
                $chunks[] = $chunk;
                $chunk = array();
                $chunkStringLength = 0;
            }
            $chunk[] = $value;
            $chunkStringLength += $entryLen;
        }

        if (!empty($chunk)) {
            $chunks[] = $chunk;
        }

        return $chunks;
    }

    /**
     * expand brace "{}" in pattern
     *
     * @param string $pattern
     * @throws \UnexpectedValueException
     * @return array of all patterns w/o braces, no duplicates
     */
    public static function expandBrace($pattern)
    {
        $stack = array($pattern);

        for ($i = 0; isset($stack[$i]); $i++) {
            $subject = $stack[$i];
            $result = self::expandBraceInnerMatch($subject, $matches);
            if (0 === $result) {
                continue;
            }
            // match
            $segments = preg_split('~\\\\.(*SKIP)(*FAIL)|,~', $matches[2]);
            $segments = array_unique(/** @scrutinizer ignore-type */ $segments);
            foreach ($segments as $segment) {
                $permutation = $matches[1] . $segment . $matches[3];
                in_array($permutation, $stack, true) || $stack[] = $permutation;
            }
            unset($stack[$i]);
        }

        // inline escaped brace characters
        $stack = array_map(function ($str) {
            return strtr($str, array(
                '\\\\' => '\\\\',
                '\\{' => '{', '\\}' => '}', '\\,' => ','
            ));
        }, $stack);

        return array_values($stack);
    }

    /**
     * Get shell environment variables only from $_SERVER in PHP CLI
     *
     * Filter an array like $_SERVER in PHP CLI shell for environment
     * variables only.
     *
     * @param array $server
     * @return array|string[]
     */
    public static function env(array $server)
    {
        return array_filter(
            // Pipelines must not support environment variable names with '=' in them
            array_flip(preg_grep('~=~', array_keys($server)))
            // What PHP might have added
            + array(
                'PHP_SELF' => null,
                'SCRIPT_NAME' => null,
                'SCRIPT_FILENAME' => null,
                'PATH_TRANSLATED' => null,
                'DOCUMENT_ROOT' => null,
                'REQUEST_TIME_FLOAT' => null,
                'REQUEST_TIME' => null,
                'argv' => null,
                'argc' => null,
            )
            + $server,
            'is_string'
        );
    }

    /**
     * check if path is absolute
     *
     * @param string $path
     * @return bool
     */
    public static function fsIsAbsolutePath($path)
    {
        // TODO: a variant with PHP stream wrapper prefix support

        $count = strspn($path, '/', 0, 3) % 2;

        return (bool)$count;
    }

    /**
     * check if path is basename
     *
     * @param string $path
     * @return bool
     */
    public static function fsIsBasename($path)
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
     * create directory if not yet exists
     *
     * @param string $path
     * @throws \RuntimeException
     */
    public static function fsMkdir($path)
    {
        if (!is_dir($path)) {
            /** @noinspection NestedPositiveIfStatementsInspection */
            if (!mkdir($path, 0777, true) && !is_dir($path)) {
                // @codeCoverageIgnoreStart
                throw new \RuntimeException(
                    sprintf('Directory "%s" was not created', $path)
                );
                // @codeCoverageIgnoreEnd
            }
        }
    }

    /**
     * @param string $link
     */
    public static function fsUnlink($link)
    {
        if (is_link($link)) {
            unlink($link);
        }
    }

    /**
     * create symbolic link, recreate if it exists
     *
     * @param string $target
     * @param string $link
     */
    public static function fsSymlink($target, $link)
    {
        self::fsUnlink($link);
        symlink($target, $link);
    }

    /**
     * locate (readable) file by basename upward all parent directories
     *
     * @param string $basename
     * @param string $directory [optional] directory to operate from, defaults
     *               to "." (relative path of present working directory)
     * @return null|string
     */
    public static function fsFileLookUp($basename, $directory = null)
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
            if (Lib::fsIsReadableFile($test)) {
                return $test;
            }
        }

        return null;
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function fsIsStreamUri($path)
    {
        $scheme = parse_url($path, PHP_URL_SCHEME);
        if (null === $scheme) {
            return false;
        }

        return in_array($scheme, stream_get_wrappers(), true);
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function fsIsReadableFile($path)
    {
        if (!is_file($path)) {
            return false;
        }

        return is_readable($path) ?: self::fsCanFopen($path, 'rb');
    }

    /**
     * @param string $path
     * @param string $mode [optional]
     * @return bool
     */
    public static function fsCanFopen($path, $mode = null)
    {
        if (null === $mode) {
            $mode = 'rb';
        }

        $handle = @fopen($path, $mode);
        if (false === $handle) {
            return false;
        }

        /** @scrutinizer ignore-unhandled */ @fclose($handle);

        return true;
    }

    /**
     * fallback for the php 5.3 version which does not have PHP_BINARY.
     *
     * @return string
     */
    public static function phpBinary()
    {
        return defined('PHP_BINARY') ? constant('PHP_BINARY') : PHP_BINDIR . '/php';
    }

    /**
     * @param string $subject
     * @param array $matches
     * @throws UnexpectedValueException
     * @return false|int
     */
    private static function expandBraceInnerMatch($subject, &$matches)
    {
        $result = preg_match_all(
            '~(\\\\.(*SKIP)(*FAIL)|(?P<token>[,{}]))~',
            $subject,
            $lookup,
            PREG_OFFSET_CAPTURE
        );

        if (false === $result) {
            throw new UnexpectedValueException('regex pattern failure'); // @codeCoverageIgnore
        }

        if (0 === $result) {
            return $result;
        }

        $open = null;
        $comma = null;

        foreach ($lookup['token'] as $token) {
            list($type, $pos) = $token;
            if ('{' === $type) {
                $open = $token;
                $comma = null;
            } elseif (',' === $type) {
                $comma = $token;
            } elseif ($open && $comma) {
                $matches = array(
                    $subject,
                    substr($subject, 0, $open[1]),
                    substr($subject, $open[1] + 1, $pos - $open[1] - 1),
                    substr($subject, $pos + 1),
                );

                return 1;
            }
        }

        return 0;
    }
}
