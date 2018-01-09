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

    /**
     * expand brace "{}" in pattern
     *
     * @param string $pattern
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
            foreach (array_unique(preg_split('~\\\\.(*SKIP)(*FAIL)|,~', $matches[2])) as $segment) {
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
     * @param string $subject
     * @param array $matches
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
            throw new \UnexpectedValueException('regex pattern failure'); // @codeCoverageIgnore
        }

        if (0 === $result) {
            return $result;
        }

        $open = null;
        $comma = null;

        foreach ($lookup['token'] as $token) {
            list($type, $pos) = $token;
            if ($type === '{') {
                $open = $token;
                $comma = null;
            } elseif ($type === ',') {
                $comma = $token;
            } elseif ($open && $comma) {
                $matches = array(
                    $subject,
                    substr($subject, 0, $open[1]),
                    substr($subject, $open[1] + 1, $token[1] - $open[1] - 1),
                    substr($subject, $token[1] + 1),
                );
                return 1;
            }
        }
        return 0;
    }

    /**
     * Is a Docker image name (optionally with a tag) syntactically
     * valid?
     *
     * @see doc/DOCKER-NAME-TAG.md
     *
     * @param string $name of docker image
     * @return bool
     */
    public static function validDockerImage($name)
    {
        $pattern =
            '{^' .
                '([^_\x00-\x20\x7F-\xFF]+(:[0-9]+)?/)?' . # <prefix>
                '([a-z0-9]+(?:(?:\.|__?|-+)[a-z0-9]+)*)' . # <name-components>
                '(:[a-zA-Z0-9_][a-zA-Z0-9_.-]{0,127})?' . # <tag-name>
            '$}';

        $result = preg_match($pattern, $name);

        return 1 === $result;
    }
}
