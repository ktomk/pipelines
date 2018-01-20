<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

/**
 * Class Image
 *
 * @package Ktomk\Pipelines\File
 */
class Image
{
    /**
     * @var ImageName
     */
    private $name;

    /**
     * @var array
     */
    private $properties;

    /**
     * Image constructor.
     *
     * @param string|array $image
     */
    public function __construct($image)
    {
        $this->parse($image);
    }

    /**
     * @param string|array $image
     */
    private function parse($image)
    {
        if (is_string($image)) {
            $this->parseString($image);
        } elseif (is_array($image)) {
            $this->parseArray($image);
        } else {
            ParseException::__(
                "'image' expects either 'a string' or 'a section'"
            );
        }
    }

    /**
     * @param string $name
     */
    private function parseString($name)
    {
        if (!ImageName::validate($name)) {
            ParseException::__(
                sprintf("'image' invalid Docker image name: '%s'", $name)
            );
        }

        $this->name = new ImageName($name);
        $this->properties = array();
    }

    private function parseArray(array $image)
    {
        if (!isset($image['name'])) {
            ParseException::__("'image' needs a name");
        }
        $this->parseString($image['name']);
        unset($image['name']);
        $entries = array('username', 'password', 'email', 'aws');
        foreach ($entries as $key) {
            $image = $this->parseArrayEntry($image, $key);
        }

        if (!empty($image)) {
            ParseException::__(sprintf(
                "unknown 'image' property '%s', expects either a string or a section",
                key($image)
            ));
        }
    }

    private function parseArrayEntry(array $image, $key)
    {
        if (isset($image[$key])) {
            $this->properties[$key] = $image[$key];
        }
        unset($image[$key]);
        return $image;
    }

    /**
     * @return ImageName image name
     */
    public function getName()
    {
        return $this->name;
    }

    public function __toString()
    {
        return (string)$this->name;
    }
}
