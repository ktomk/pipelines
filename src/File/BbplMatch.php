<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\Lib;

/**
 * Library for pattern matching
 *
 * @package Ktomk\Pipelines\File
 */
class BbplMatch
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
     * @throws \UnexpectedValueException
     * @return bool
     */
    public static function match($pattern, $subject) {
        $regex = implode(
            '|',
            array_map(array('self', 'map'), Lib::expandBrace($pattern))
        );

        return (bool)preg_match(
            '~^(' . $regex . ')$~',
            $subject
        );
    }

    private static function map($pattern)
    {
        return strtr($pattern, self::$map);
    }
}
