<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\Value\Properties;

/**
 * Class Image
 *
 * @package Ktomk\Pipelines\File\File
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
     * if an 'image' entry is set, validate it is a string or a section.
     *
     * @param array $array
     * @throws ParseException if the image name is invalid
     */
    public static function validate(array $array)
    {
        if (!array_key_exists('image', $array)) {
            return;
        }

        $image = $array['image'];

        if (is_array($image) && isset($image['name'])) {
            if (!ImageName::validate($image['name'])) {
                ParseException::__(sprintf(
                    "'image' invalid Docker image name: '%s'",
                    $image['name']
                ));
            }

            return;
        }

        if (!is_string($image)) {
            ParseException::__("'image' requires a Docker image name");
        }
        if (!ImageName::validate($image)) {
            ParseException::__(
                sprintf("'image' invalid Docker image name: '%s'", $image)
            );
        }
    }

    /**
     * Image constructor.
     *
     * @param array|string $image
     * @throws \Ktomk\Pipelines\File\ParseException
     */
    public function __construct($image)
    {
        $this->properties = new Properties();
        $this->parse($image);
    }

    public function __toString()
    {
        return (string)$this->name;
    }

    /**
     * @return ImageName image name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Properties properties additional to name
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'name' => (string)$this->getName()
        );
    }

    /**
     * @param array|string $image
     * @throws \Ktomk\Pipelines\File\ParseException
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
     * @throws \Ktomk\Pipelines\File\ParseException
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

    /**
     * @param array $image
     * @throws \Ktomk\Pipelines\File\ParseException
     */
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
}
