<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

/**
 * Pipelines cli arguments exception
 */
class ArgsException extends \Exception
{
    /**
     * Abstract method to throw this exception
     *
     * @param string $message
     * @param int $code
     * @throws ArgsException
     */
    public static function __($message, $code = 1)
    {
        throw new self($message, $code);
    }
}
