<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Containers;

use Ktomk\Pipelines\Project;
use UnexpectedValueException;

/**
 * Class NameBuilder
 *
 * @package Ktomk\Pipelines\Runner\Containers
 */
abstract class NameBuilder
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @var string
     */
    private $role;

    /**
     * @param string $string
     * @param string $replacement [optional] defaults to dash "-"
     * @param string $fallBack [optional] defaults to empty string
     *
     * @return string
     */
    public static function slugify($string, $replacement = null, $fallBack = null)
    {
        null === $replacement && $replacement = '-';

        // all non-allowed characters -> replacement (which is normally a separator "_", "." or "-")
        $buffer = preg_replace('([^a-zA-Z0-9_.-]+)', (string)$replacement, (string)$string);
        if (false === $buffer) {
            // @codeCoverageIgnoreStart
            throw new UnexpectedValueException('regex operation failed');
            // @codeCoverageIgnoreEnd
        }

        // multiple separator(s) after each other -> one replacement (which is normally a separator)
        $buffer = preg_replace('(([_.-])[_.-]+)', (string)$replacement, $buffer);

        // not starting nor ending with a separator
        $buffer = trim($buffer, '_.-');

        // not starting with a number
        $buffer = preg_replace('(^\d+([_.-]\d+)*)', (string)$replacement, $buffer);

        // multiple separator(s) after each other -> one replacement (which is normally a separator)
        $buffer = preg_replace('(([_.-])[_.-]+)', (string)$replacement, $buffer);

        // not starting nor ending with a separator
        $buffer = trim($buffer, '_.-');

        // separator(s) only -> empty string
        $buffer = preg_replace('(^[_.-]+$)', '', $buffer);

        return '' === (string)$buffer ? (string)$fallBack : (string)$buffer;
    }
}
