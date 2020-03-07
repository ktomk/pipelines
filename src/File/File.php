<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use InvalidArgumentException;
use Ktomk\Pipelines\Glob;
use Ktomk\Pipelines\Runner\Reference;
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
     * @var array
     */
    private $pipelines;

    /**
     * @var array
     */
    private static $sections = array('branches', 'tags', 'bookmarks', 'pull-requests', 'custom');

    /**
     * @param $path
     *
     * @throws ParseException
     * @return File
     */
    public static function createFromFile($path)
    {
        $result = Yaml::file($path);
        if (!$result) {
            ParseException::__(sprintf('YAML error: %s; verify the file contains valid YAML', $path));
        }

        return new self($result);
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
            ParseException::__("Missing required property 'pipelines'");
        }

        // quick validation: image name
        Image::validate($array);

        $this->pipelines = $this->parsePipelineReferences($array['pipelines']);

        $this->array = $array;
    }

    /**
     * @throws ParseException
     * @return Image
     */
    public function getImage()
    {
        $imageData = isset($this->array['image'])
            ? $this->array['image']
            : self::DEFAULT_IMAGE;

        return new Image($imageData);
    }

    public function getClone()
    {
        return isset($this->array['clone'])
            ? $this->array['clone']
            : self::DEFAULT_CLONE;
    }

    /**
     * @throws InvalidArgumentException
     * @return null|Pipeline
     */
    public function getDefault()
    {
        return $this->getById('default');
    }

    /**
     * returns the id of the default pipeline in file or null if there is none
     *
     * @return null|string
     */
    public function getIdDefault()
    {
        $id = 'default';

        if (!isset($this->pipelines[$id])) {
            return null;
        }

        return $id;
    }

    /**
     * Searches the pipeline that matches the reference
     *
     * @param Reference $reference
     *
     * @throws \UnexpectedValueException
     * @throws InvalidArgumentException
     * @return null|string id if found, null otherwise
     */
    public function searchIdByReference(Reference $reference)
    {
        if (null === $reference->getType()) {
            return $this->getIdDefault();
        }

        return $this->searchIdByTypeReference(
            $reference->getPipelinesType(),
            $reference->getName()
        );
    }

    /**
     * Searches a reference
     *
     * @param Reference $reference
     *
     * @throws ParseException
     * @throws \UnexpectedValueException
     * @throws InvalidArgumentException
     * @return null|Pipeline
     */
    public function searchReference(Reference $reference)
    {
        if (null === $type = $reference->getPipelinesType()) {
            return $this->getDefault();
        }

        return $this->searchTypeReference($type, $reference->getName());
    }

    /**
     * Searches a reference within type, returns found one, if
     * none is found, the default pipeline or null if there is
     * no default pipeline.
     *
     * @param string $type of pipeline, can be branches, tags or bookmarks
     * @param string $reference
     *
     * @throws ParseException
     * @throws \UnexpectedValueException
     * @throws InvalidArgumentException
     * @return null|Pipeline
     */
    public function searchTypeReference($type, $reference)
    {
        $id = $this->searchIdByTypeReference($type, $reference);

        return null !== $id ? $this->getById($id) : null;
    }

    /**
     * @return array
     */
    public function getPipelineIds()
    {
        return array_keys($this->pipelines);
    }

    /**
     * @throws InvalidArgumentException
     * @throws ParseException
     * @return array|Pipeline[]
     */
    public function getPipelines()
    {
        $pipelines = array();

        foreach ($this->getPipelineIds() as $id) {
            if (!$this->isIdValid($id)) {
                ParseException::__(sprintf("invalid pipeline id '%s'", $id));
            }
            $pipelines[$id] = $this->getById($id);
        }

        return $pipelines;
    }

    /**
     * @param string $id
     * @throws InvalidArgumentException
     * @throws ParseException
     * @return null|Pipeline
     */
    public function getById($id)
    {
        if (!$this->isIdValid($id)) {
            throw new InvalidArgumentException(sprintf("Invalid id '%s'", $id));
        }

        if (!isset($this->pipelines[$id])) {
            return null;
        }

        $ref = $this->pipelines[$id];
        if ($ref[2] instanceof Pipeline) {
            return $ref[2];
        }

        // bind to instance if yet an array
        if (!is_array($ref[2])) {
            ParseException::__(sprintf('%s: named pipeline required', $id));
        }
        $pipeline = new Pipeline($this, $ref[2]);
        $ref[2] = $pipeline;

        return $pipeline;
    }

    public function getIdFrom(Pipeline $pipeline)
    {
        foreach ($this->pipelines as $id => $reference) {
            if ($pipeline === $reference[2]) {
                return $id;
            }
        }

        return null;
    }

    private function isIdValid($id)
    {
        return (bool)preg_match('~^(default|(' . implode('|', self::$sections) . ')/[^\x00-\x1F\x7F-\xFF]*)$~', $id);
    }

    /**
     * @param $type
     * @param $reference
     * @throws \UnexpectedValueException
     * @throws InvalidArgumentException
     * @return null|string
     */
    private function searchIdByTypeReference($type, $reference)
    {
        $this->validateType($type);

        if (!isset($this->array['pipelines'][$type])) {
            return $this->getIdDefault();
        }
        $array = &$this->array['pipelines'][$type];

        # check for direct (non-pattern) match
        if (isset($array[$reference])) {
            return "${type}/${reference}";
        }

        # get entry with largest pattern to match
        $patterns = array_keys($array);
        unset($array);

        $match = '';
        foreach ($patterns as $pattern) {
            $pattern = (string)$pattern;
            $result = Glob::match($pattern, $reference);
            if ($result && (strlen($pattern) > strlen($match))) {
                $match = $pattern;
            }
        }
        if ('' !== $match) {
            return "${type}/${match}";
        }

        return $this->getIdDefault();
    }

    /**
     * @param array $array
     *
     * @throws ParseException
     * @return array
     */
    private function parsePipelineReferences(array &$array)
    {
        // quick validation: pipeline sections
        $sections = self::$sections;
        $count = 0;
        foreach ($sections as $section) {
            if (isset($array[$section])) {
                $count++;
            }
        }
        if (!$count && !isset($array['default'])) {
            $middle = implode(', ', array_slice($sections, 0, -1));
            ParseException::__("'pipelines' requires at least a default, ${middle} or custom section");
        }

        $references = array();

        $section = 'default';
        if (isset($array[$section])) {
            if (!is_array($array[$section])) {
                ParseException::__("'${section}' requires a list of steps");
            }
            $references[$section] = array(
                $section,
                null,
                &$array[$section],
            );
        }

        foreach ($array as $section => $refs) {
            if (!in_array($section, $sections, true)) {
                continue;
            }
            if (!is_array($refs)) {
                ParseException::__("'${section}' requires a list");
            }
            foreach ($refs as $pattern => $pipeline) {
                $references["${section}/${pattern}"] = array(
                    $section,
                    $pattern,
                    &$array[$section][$pattern],
                );
            }
        }

        return $references;
    }

    /**
     * @param $type
     * @throws InvalidArgumentException
     */
    private function validateType($type)
    {
        $scopes = array_slice(self::$sections, 0, 4);
        if (!in_array($type, $scopes, true)) {
            throw new InvalidArgumentException(sprintf("Invalid type '%s'", $type));
        }
    }
}
