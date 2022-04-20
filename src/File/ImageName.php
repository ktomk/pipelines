<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

/**
 * image name parser and value object
 */
class ImageName
{
    /**
     * @var string
     */
    private $name;

    /**
     * validate image name
     *
     * Is a Docker image name (optionally with a tag or digest)
     * syntactically valid?
     *
     * @see doc/DOCKER-NAME-TAG.md
     *
     * @param string $name of docker image
     *
     * @return bool
     */
    public static function validate($name)
    {
        $pattern
            = '{^'
            . '([a-zA-Z0-9.-]+(:[0-9]+)?/)?' # prefix
            . '([a-z0-9]+(?:(?:\.|__?|-+)[a-z0-9]+)*)(/[a-z0-9]+(?:(?:\.|__?|-+)[a-z0-9]+)*)*' # name-components
            . '(:[a-zA-Z0-9_][a-zA-Z0-9_.-]{0,127}|@[a-z0-9]+([+._-][a-z0-9]+)*:[a-zA-Z0-9=_-]+)?' # ":" tag-name | "@" digest
            . '$}';

        $result = preg_match($pattern, $name);

        return 1 === $result;
    }

    /**
     * @param string $name
     *
     * @throws ParseException
     */
    public function __construct($name)
    {
        $this->parse($name);
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @throws ParseException
     *
     * @return void
     */
    private function parse($name)
    {
        if (!self::validate($name)) {
            throw new ParseException(sprintf(
                "'image' invalid Docker image name: '%s'",
                $name
            ));
        }

        $this->name = (string)$name;
    }
}
