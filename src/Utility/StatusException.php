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
     * fatal with message and non-zero (1)
     *
     * @param string $message
     * @param int $code
     *
     * @throws StatusException
     */
    public static function fatal($message = '', $code = 1)
    {
        if ('' === $message) {
            $message = 'fatal abort';
        }

        throw new self($message, $code);
    }

    /**
     * ok (success, zero)
     *
     * @throws StatusException
     */
    public static function ok()
    {
        throw new self('', 0);
    }

    /**
     * StatusException constructor.
     *
     * @param string $message
     * @param int|string $code
     */
    public function __construct($message = '', $code = 0)
    {
        if (!is_int($code) || $code < 0 || $code > 255) {
            throw new \InvalidArgumentException(sprintf(
                'Code must be integer in range from 0 to 255, %s given',
                var_export($code, true)
            ));
        }

        parent::__construct($message, $code);
    }
}
