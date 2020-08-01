<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Value;

use UnexpectedValueException;

/**
 * Pipelines Prefix
 *
 * @package Ktomk\Pipelines\Value
 */
abstract class Prefix
{
    /**
     * @param string $prefix
     *
     * @return string
     */
    public static function verify($prefix)
    {
        if (preg_match('~^[a-z]{3,}$~', $prefix)) {
            return $prefix;
        }

        throw new UnexpectedValueException(sprintf(
            'invalid prefix: "%s"; a prefix is only lower-case letters with a minimum length of three characters',
            $prefix
        ));
    }
}
