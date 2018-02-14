<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\Value\Properties;

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
     * @var Properties
     */
    private $properties;

    /**
     * Image constructor.
     *
     * @param string|array $image
     */
    public function __construct($image)
    {
        $this->properties = new Properties();
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
    }

    private function parseArray(array $image)
    {
        if (!isset($image['name'])) {
            ParseException::__("'image' needs a name");
        }
        $this->parseString($image['name']);
        unset($image['name']);

        $entries = array('run-as-user', 'username', 'password', 'email', 'aws');
        $image = $this->properties->import($image, $entries);

        if (!empty($image)) {
            ParseException::__(sprintf(
                "unknown 'image' property '%s', expects either a string or a section",
                key($image)
            ));
        }
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

    /**
     * @return Properties properties additional to name
     */
    public function getProperties()
    {
        return $this->properties;
    }
}
