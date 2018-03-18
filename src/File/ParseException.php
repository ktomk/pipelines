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
     * @var string
     */
    private $parseMessage;

    /**
     * Abstract method to throw this message
     *
     * @param string $message
     * @throws ParseException
     */
    public static function __($message)
    {
        $self = new self(
            sprintf('file parse error: %s', $message),
            2
        );

        $self->parseMessage = $message;

        throw $self;
    }

    /**
     * @return string
     */
    public function getParseMessage()
    {
        return $this->parseMessage;
    }
}
