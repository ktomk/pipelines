<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Exception;

/**
 * Signal utility exit status, optionally with an (error) message
 */
class StatusException extends Exception
{
    /**
     * @param int $code
     * @param string $message
     * @throws StatusException
     */
    public static function status($code = 0, $message = '')
    {
        throw new self($message, $code);
    }
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        if (!is_int($code) || $code < 0 || $code > 255) {
            throw new \InvalidArgumentException(sprintf(
                'Code must be integer in range from 0 to 255, %s given',
                var_export($code, true)
            ));
        }

        parent::__construct($message, $code, $previous);
    }
}
