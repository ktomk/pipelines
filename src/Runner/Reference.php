<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;


class Reference
{
    const BRANCH_TAG_BOOKMARK = '~^(branch|tag|bookmark):(.+)$~';

    private $string;

    private $type;

    private $value;

    private $map = array(
        'bookmark' => 'bookmarks',
        'branch' => 'branches',
        'tag' => 'tags',
    );

    /**
     * @param null $string
     * @return Reference
     */
    public static function create($string = null)
    {
        return new self($string);
    }

    /**
     * Validates if a string is a valid (non-null) reference
     * @param $string
     * @return bool
     */
    public static function valid($string)
    {
        $result = preg_match(self::BRANCH_TAG_BOOKMARK, $string);

        return (bool)$result;
    }

    public function __construct($string = null)
    {
        $this->parse($string);
    }

    private function parse($string)
    {
        $this->string = $string;

        if (null === $string) {
            return;
        }

        $result = preg_match(self::BRANCH_TAG_BOOKMARK, $string, $matches);

        if (!$result) {
            throw new \InvalidArgumentException(sprintf('invalid reference: "%s"', $string));
        }

        $this->type = $matches[1];
        $this->value = $matches[2];
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getPipelinesType()
    {
        return $this->type ? $this->map[$this->type] : null;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->value;
    }
}
