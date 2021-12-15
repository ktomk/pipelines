<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use InvalidArgumentException;
use Ktomk\Pipelines\Runner\Reference;

/**
 * Class Pipelines
 *
 * @package Ktomk\Pipelines\File
 */
class Pipelines implements Dom\FileNode
{
    /**
     * @var array
     */
    protected $array;

    /**
     * @var null|File
     */
    protected $file;

    /**
     * @var array
     */
    protected $references;

    /**
     * Pipelines constructor.
     *
     * @param array $array
     * @param null|File $file
     */
    public function __construct(array $array, File $file = null)
    {
        $this->file = $file;

        $this->references = $this->parsePipelineReferences($array);

        $this->array = $array;
    }

    /**
     * @throws InvalidArgumentException
     * @throws ParseException
     *
     * @return null|Pipeline
     */
    public function getDefault()
    {
        return PipelinesReferences::byId($this, 'default');
    }

    /**
     * returns the id of the default pipeline in file or null if there is none
     *
     * @return null|string
     */
    public function getIdDefault()
    {
        return PipelinesReferences::idDefault($this);
    }

    /**
     * @return array|string[]
     */
    public function getPipelineIds()
    {
        return array_map('strval', array_keys($this->references));
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
        return PipelinesReferences::byId($this, $id);
    }

    /**
     * get id of a pipeline
     *
     * @param Pipeline $pipeline
     *
     * @return null|string
     */
    public function getId(Pipeline $pipeline)
    {
        return PipelinesReferences::id($this, $pipeline);
    }

    /**
     * Searches a reference
     *
     * @param Reference $reference
     *
     * @throws InvalidArgumentException
     * @throws ParseException
     *
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
     * @param null|string $reference
     *
     * @throws InvalidArgumentException
     * @throws ParseException
     *
     * @return null|Pipeline
     */
    public function searchTypeReference($type, $reference)
    {
        $id = $this->searchIdByTypeReference($type, $reference);

        return null !== $id ? $this->getById($id) : null;
    }

    /**
     * Searches the pipeline that matches the reference
     *
     * @param Reference $reference
     *
     * @throws InvalidArgumentException
     *
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
     * @return null|File
     */
    public function getFile()
    {
        if ($this->file instanceof File) {
            return $this->file;
        }

        return null;
    }

    /**
     * @throws InvalidArgumentException
     * @throws ParseException
     *
     * @return array|Pipeline[]
     */
    public function getPipelines()
    {
        $pipelines = array();

        foreach ($this->getPipelineIds() as $id) {
            if (!ReferenceTypes::isValidId($id)) {
                throw new ParseException(sprintf("invalid pipeline id '%s'", $id));
            }
            $pipelines[$id] = $this->getById($id);
        }

        return $pipelines;
    }

    /**
     * Index all pipelines as references array map by id
     *
     * a reference is array($section (or default), $pattern (or null for default), &$arrayFileParseData)
     * references are keyed by their pipeline id
     *
     * @param array $array
     *
     * @throws ParseException
     *
     * @return array
     */
    private function parsePipelineReferences(array &$array)
    {
        $this->parseValidatePipelines($array);

        $references = $this->referencesDefault($array);

        $references = $this->referencesAddSections($references, $array);

        return $references;
    }

    /**
     * quick validation of pipeline sections (default pipeline + sections)
     *
     * there must be at least one pipeline in the file
     *
     * NOTE: the check is incomplete, as it assumes any section contains at
     *       least one pipeline which is unchecked
     *
     * @param array $array
     *
     * @return void
     */
    private function parseValidatePipelines(array $array)
    {
        $sections = ReferenceTypes::getSections();
        $count = 0;
        foreach ($sections as $section) {
            if (isset($array[$section])) {
                $count++;
            }
        }
        if (!$count && !isset($array['default'])) {
            $middle = implode(', ', array_slice($sections, 0, -1));

            throw new ParseException("'pipelines' requires at least a default, ${middle} or custom section");
        }
    }

    /**
     * create references by default pipeline
     *
     * @param array $array
     *
     * @return array
     */
    private function referencesDefault(array &$array)
    {
        $references = array();

        $default = ReferenceTypes::SEG_DEFAULT;

        if (!isset($array[$default])) {
            return $references;
        }

        if (!is_array($array[$default])) {
            throw new ParseException("'${default}' requires a list of steps");
        }

        $references[$default] = array($default, null, &$array[$default]);

        return $references;
    }

    /**
     * add section pipelines to references
     *
     * @param array $references
     * @param array $array
     *
     * @return array
     */
    private function referencesAddSections(array $references, array &$array)
    {
        // reference section pipelines
        $sections = ReferenceTypes::getSections();

        foreach ($array as $section => $refs) {
            if (!in_array($section, $sections, true)) {
                continue; // not a section for references
            }
            if (!is_array($refs)) {
                throw new ParseException("'${section}' requires a list");
            }
            foreach ($refs as $pattern => $pipeline) {
                $references["${section}/${pattern}"] = array(
                    (string)$section,
                    (string)$pattern,
                    &$array[$section][$pattern],
                );
            }
        }

        return $references;
    }

    /**
     * @param null|string $type
     * @param null|string $reference
     *
     * @throws InvalidArgumentException
     *
     * @return null|string
     */
    private function searchIdByTypeReference($type, $reference)
    {
        return PipelinesReferences::idByTypeReference($this, $type, $reference);
    }
}
