<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use InvalidArgumentException;

/**
 * Bitbucket Pipelines file parsing exception
 */
class ParseException extends InvalidArgumentException
{
    /**
     * Abstract method to throw this message
     *
     * @param string $message
     */
    static function __($message)
    {
        throw new self($message);
    }
}
