<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility\Option;

use Ktomk\Pipelines\LibFsPath;

/**
 * Types
 *
 * Defines which types there are and knows how to
 * verify them.
 *
 * CLI options context
 */
final class Types
{
    const ABSPATH = 1;

    /**
     * CLI input (e.g. -c <name>=<value>) verification
     *
     * @param string $value
     * @param null|int $type
     *
     * @return null|string if input could not be verified, string otherwise
     */
    public function verifyInput($value, $type)
    {
        switch ($type) {
            case null:
                return $value;
            case self::ABSPATH:
                return $this->verifyAbspath($value);
            default:
                throw new \LogicException(sprintf('not a type: %s', $type));
        }
    }

    /**
     * verify a path is an absolute path without
     * any dot and dot-dot parts.
     *
     * @param string $value
     *
     * @return null|string string if input $value is already the abspath, null otherwise
     */
    public function verifyAbspath($value)
    {
        $buffer = '/' . ltrim(LibFsPath::normalizeSegments($value), '/.');

        return $buffer === $value ? $buffer : null;
    }
}
