<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;


class ArgsException extends \Exception
{
    /**
     * @param $message
     * @param int $code
     * @throws ArgsException
     */
    public static function give($message, $code = 1)
    {
        throw new ArgsException($message, $code);
    }
}
