<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use UnexpectedValueException;

/**
 * Library for pattern matching
 *
 * @package Ktomk\Pipelines
 */
class Glob
{
    private static $map = array(
        '\\\\' => '[\\\\]', '\\*' => '[*]', '\\?' => '[?]', '\\' => '[\\\\]',
        '^' => '\\^', '$' => '[$]',
        '|' => '[|]', '+' => '[+]',
        '[' => '[[]', ']' => '[]]',
        '{' => '[{]', '}' => '[}]',
        '(' => '[(]', ')' => '[)]',
        '<' => '[<]', '>' => '[>]',
        '**' => '.*',
        '*' => '[^/]*', '?' => '[^/]?',
        '.' => '[.]',
        '=' => '[=]', '!' => '[!]',
        ':' => '[:]', '-' => '[-]',
        '~' => '\\~',
    );

    /**
     * glob pattern match
     *
     * @param string $pattern
     * @param string $subject
     *
     * @throws UnexpectedValueException
     *
     * @return bool
     */
    public static function match($pattern, $subject)
    {
        $regex = implode(
            '|',
            array_map(array('Ktomk\Pipelines\Glob', 'map'), self::expandBrace($pattern))
        );

        return (bool)preg_match(
            '~^(' . $regex . ')$~',
            $subject
        );
    }

    /**
     * expand brace "{}" in pattern
     *
     * @param string $pattern
     *
     * @throws UnexpectedValueException
     *
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
                '\\{' => '{', '\\}' => '}', '\\,' => ',',
            ));
        }, $stack);

        return array_values($stack);
    }

    /**
     * @param string $subject
     * @param array $matches
     *
     * @throws UnexpectedValueException
     *
     * @return int
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

    /**
     * map pattern
     *
     * via translation map to preserve glob and standard characters for pcre
     * pattern
     *
     * @param string $pattern
     *
     * @return string
     */
    private static function map($pattern)
    {
        return strtr($pattern, self::$map);
    }
}
