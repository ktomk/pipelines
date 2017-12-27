<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;


class Lib
{
    /**
     * @return string
     */
    static function generateUuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

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
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    static function cmd($command, array $arguments)
    {
        $buffer = $command;

        $arguments = call_user_func_array('self::merge', $arguments);

        foreach ($arguments as $argument) {
            $buffer .= ' ' . self::quoteArg($argument);
        }

        return $buffer;
    }

    static function quoteArg($argument)
    {
        $parts = explode("'", $argument);

        $buffer = '';
        foreach ($parts as $index => $part) {
            $index && $buffer .= "\\'";
            $safe = preg_match('~^[a-zA-Z0-9,._+@%/-]*$~', $part);
            $buffer .= $safe ? $part : "'$part'";
        }

        if ($buffer === "") {
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
     * @return array
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
                $arrays[$key] = (array) $value;
            }
        }

        return call_user_func_array('array_merge', $arrays);
    }
}
