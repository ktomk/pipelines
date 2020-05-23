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
     * ParseException constructor.
     *
     * @param string $message
     */
    public function __construct($message = '')
    {
        $this->parseMessage = $message;
        parent::__construct(sprintf('file parse error: %s', $message), 2);
    }

    /**
     * @return string
     */
    public function getParseMessage()
    {
        return $this->parseMessage;
    }
}
