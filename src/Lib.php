<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

class Lib
{
    /**
     * @param mixed $v
     * @param mixed $d
     *
     * @return mixed
     */
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
     *
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

    /**
     * @param string $command
     * @param array|string[] $arguments
     *
     * @return string
     */
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
     *
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
     * Handles (no) newline at the end of buffer
     *
     * @param string $buffer
     *
     * @return array|string[]
     */
    public static function lines($buffer)
    {
        $lines = explode("\n", $buffer);
        $c = count($lines);
        if ($c && '' === $lines[$c - 1]) {
            array_pop($lines);
        }

        return $lines;
    }

    /**
     * merge n parameters, if a scalar, turned into array, otherwise must be an array
     *
     * @return array
     */
    public static function merge()
    {
        if (!$arrays = func_get_args()) {
            return $arrays;
        }

        foreach ($arrays as $key => $value) {
            if (!is_array($value)) {
                $arrays[$key] = null === $value ? array() : array($value);
            }
        }

        return call_user_func_array('array_merge', $arrays);
    }

    /**
     * Chunk an array of strings based on maximum string length per chunk
     *
     * @param array|string[] $array
     * @param int $maxLength
     * @param int $overHeadPerEntry
     *
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
                    throw new \InvalidArgumentException(sprintf(
                        'maximum length of %d is too little to chunk the array at %s %s (%d chunk(s) so far)',
                        $maxLength,
                        is_string($key) ? 'key' : 'index',
                        is_string($key) ? var_export($key, true) : (int)$key,
                        count($chunks)
                    ));
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
     * Get shell environment variables only from $_SERVER in PHP CLI
     *
     * Filter an array like $_SERVER in PHP CLI shell for environment
     * variables only.
     *
     * @param array $server
     *
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
     * fallback for the php 5.3 version which does not have PHP_BINARY.
     *
     * @return string
     */
    public static function phpBinary()
    {
        return defined('PHP_BINARY') ? constant('PHP_BINARY') : PHP_BINDIR . '/php';
    }

    /**
     * Empty "coalesce" function
     *
     * @return mixed
     */
    public static function emptyCoalesce()
    {
        foreach (func_get_args() as $arg) {
            if (empty($arg)) {
                continue;
            }

            return $arg;
        }

        return null;
    }
}
