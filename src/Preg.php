<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use UnexpectedValueException;

/**
 * Class Preg
 *
 * @package Ktomk\Pipelines
 */
abstract class Preg
{
    /**
     * @param string $pattern
     * @param string $subject
     *
     * @return int
     */
    public static function match($pattern, $subject)
    {
        $result = @preg_match($pattern, $subject);
        if (false === $result) {
            throw new UnexpectedValueException(
                sprintf('preg_match error (%d): "%s"', preg_last_error(), $pattern)
            );
        }

        return $result;
    }
}
