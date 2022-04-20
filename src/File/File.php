<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use InvalidArgumentException;
use Ktomk\Pipelines\Runner\Reference;
use Ktomk\Pipelines\Yaml\ParseException as YamlParseException;
use Ktomk\Pipelines\Yaml\Yaml;

/**
 * Bitbucket Pipelines file
 */
class File
{
    const FILE_NAME = 'bitbucket-pipelines.yml';

    const DEFAULT_IMAGE = 'atlassian/default-image:latest';

    /**
     * default clone depth
     */
    const DEFAULT_CLONE = 50;

    /**
     * @var array
     */
    private $array;

    /**
     * @var Pipelines
     */
    private $pipelines;

    /**
     * @var null|string path to file (the visual one to re-present it back to the user)
     *
     * @see File::createFromFile()
     */
    private $path;

    /**
     * @param string $path
     *
     * @throws ParseException
     *
     * @return File
     */
    public static function createFromFile($path)
    {
        $message = sprintf('YAML error: %s; verify the file contains valid YAML', $path);

        try {
            $result = Yaml::file($path);
        } catch (YamlParseException $parseException) {
            $result = null;
            $message .= ': ' . $parseException->getMessage();
        }

        if (null === $result) {
            throw new ParseException($message);
        }

        $file = new self($result);
        $file->path = $path;

        return $file;
    }

    /**
     * File constructor.
     *
     * @param array $array
     *
     * @throws ParseException
     */
    public function __construct(array $array)
    {
        // quick validation: pipelines require
        if (!isset($array['pipelines']) || !is_array($array['pipelines'])) {
            throw new ParseException("Missing required property 'pipelines'");
        }

        // quick validation: image name
        Image::validate($array);

        $this->pipelines = $this->parsePipelines($array['pipelines']);

        $this->array = $array;
    }

    /**
     * @throws ParseException
     *
     * @return Image
     */
    public function getImage()
    {
        $imageData = isset($this->array['image'])
            ? $this->array['image']
            : self::DEFAULT_IMAGE;

        return new Image($imageData);
    }

    /**
     * @return array|int
     */
    public function getClone()
    {
        return isset($this->array['clone'])
            ? $this->array['clone']
            : self::DEFAULT_CLONE;
    }

    /**
     * @return null|Pipeline
     */
    public function getDefault()
    {
        return $this->pipelines->getDefault();
    }

    /**
     * Searches the pipeline that matches the reference
     *
     * @param Reference $reference
     *
     * @throws \UnexpectedValueException
     * @throws InvalidArgumentException
     *
     * @return null|string id if found, null otherwise
     */
    public function searchIdByReference(Reference $reference)
    {
        return $this->pipelines->searchIdByReference($reference);
    }

    /**
     * @return Pipelines
     */
    public function getPipelines()
    {
        return $this->pipelines;
    }

    /**
     * @param string $id
     *
     * @throws InvalidArgumentException
     * @throws ParseException
     *
     * @return null|Pipeline
     */
    public function getById($id)
    {
        return $this->pipelines->getById($id);
    }

    /**
     * @return Definitions
     */
    public function getDefinitions()
    {
        return new Definitions(
            isset($this->array['definitions'])
                ? $this->array['definitions'] : array()
        );
    }

    /**
     * @return Options
     */
    public function getOptions()
    {
        return new Options(
            isset($this->array['options'])
                ? $this->array['options'] : array()
        );
    }

    /**
     * @return array
     */
    public function getArray()
    {
        return $this->array;
    }

    /**
     * path to the file, null if created from array, string if created from file
     *
     * in its original form, e.g. '-' could represent from stdin
     *
     * @return null|string
     *
     * @see File::createFromFile()
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param array $array
     *
     * @return Pipelines
     */
    private function parsePipelines(array $array)
    {
        return new Pipelines($array, $this);
    }
}
