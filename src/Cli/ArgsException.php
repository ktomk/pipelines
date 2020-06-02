<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

/**
 * Pipelines cli arguments exception
 */
class ArgsException extends \Exception
{
    /**
     * ArgsException constructor.
     *
     * @param string $message
     * @param int $code
     * @param null|\Exception $previous
     */
    public function __construct($message, $code = 1, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
