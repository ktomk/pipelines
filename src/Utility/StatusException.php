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
     * @param null|Exception $previous
     *
     * @throws StatusException
     */
    public static function fatal($message = '', $code = 1, Exception $previous = null)
    {
        if ('' === $message) {
            $message = 'fatal abort';
        }

        throw new self($message, $code, $previous);
    }

    /**
     * ok (success, zero)
     *
     * @param null|Exception $previous
     *
     * @throws StatusException
     */
    public static function ok(Exception $previous = null)
    {
        throw new self('', 0, $previous);
    }

    /**
     * StatusException constructor.
     *
     * @param string $message
     * @param int|string $code
     * @param null|Exception $previous
     */
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
