<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Value;

use Ktomk\Pipelines\Utility\App;
use UnexpectedValueException;

/**
 * Pipelines Prefix
 *
 * @package Ktomk\Pipelines\Value
 */
abstract class Prefix
{
    const DEFAULT_PREFIX = App::UTILITY_NAME;

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

    /**
     * filter a prefix
     *
     * always return a verified prefix (string)
     *
     * @param null|string $prefix [optional] if null, defaults to the default prefix
     *
     * @return string verified prefix
     */
    public static function filter($prefix = null)
    {
        $buffer = $prefix;

        if (null === $buffer) {
            $buffer = self::DEFAULT_PREFIX;
        }

        return self::verify($buffer);
    }
}
