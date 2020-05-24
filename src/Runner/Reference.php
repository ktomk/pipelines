<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use InvalidArgumentException;

class Reference
{
    const TRIGGER = '~^(branch|tag|bookmark|pr):(.+)$~';

    private $string;

    private $type;

    /**
     * @var string
     */
    private $value;

    private static $map = array(
        'bookmark' => 'bookmarks',
        'branch' => 'branches',
        'pr' => 'pull-requests',
        'tag' => 'tags',
    );

    /**
     * @param null|string $string [optional] use null for a null reference
     *
     * @throws InvalidArgumentException
     *
     * @return Reference
     */
    public static function create($string = null)
    {
        return new self($string);
    }

    /**
     * Validates if a string is a valid (non-null) reference
     *
     * @param $string
     *
     * @return bool
     */
    public static function valid($string)
    {
        $result = preg_match(self::TRIGGER, $string);

        return (bool)$result;
    }

    /**
     * Reference constructor
     *
     * @param null|string $string [optional] use null for a null reference
     *
     * @throws InvalidArgumentException
     */
    public function __construct($string = null)
    {
        $this->parse($string);
    }

    /**
     * @return null|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return null|string
     */
    public function getPipelinesType()
    {
        return $this->type ? self::$map[$this->type] : null;
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->value;
    }

    /**
     * @param null|string $string
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    private function parse($string)
    {
        $this->string = $string;

        if (null === $string) {
            return;
        }

        $result = preg_match(self::TRIGGER, $string, $matches);

        if (!$result) {
            throw new InvalidArgumentException(sprintf('invalid reference: "%s"', $string));
        }

        $this->type = $matches[1];
        $this->value = $matches[2];
    }
}
