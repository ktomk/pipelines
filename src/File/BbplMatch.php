<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

/**
 * Library for pattern matching
 *
 * @package Ktomk\Pipelines\File
 */
class BbplMatch
{
    static $map = array(
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
     * @param string $pattern
     * @param string $subject
     * @return bool
     */
    public static function match($pattern, $subject) {
        return (bool)preg_match(
            '~^' . strtr($pattern, self::$map) . '$~',
            $subject
        );
    }
}
