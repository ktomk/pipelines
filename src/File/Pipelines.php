<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use InvalidArgumentException;
use Ktomk\Pipelines\Glob;
use Ktomk\Pipelines\Runner\Reference;

/**
 * Class Pipelines
 *
 * @package Ktomk\Pipelines\File
 */
class Pipelines
{
    /**
     * @var array
     */
    private $array;

    /**
     * @var null|File
     */
    private $file;

    /**
     * @var array
     */
    private $pipelines;

    /**
     * Pipelines constructor.
     *
     * @param array $array
     * @param null|File $file
     */
    public function __construct(array $array, File $file = null)
    {
        $this->file = $file;

        $this->pipelines = $this->parsePipelineReferences($array);

        $this->array = $array;
    }

    /**
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
     * @return array
     */
    public function getPipelineIds()
    {
        return array_keys($this->pipelines);
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
        if (!ReferenceTypes::isValidId($id)) {
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
            throw new ParseException(sprintf('%s: named pipeline required', $id));
        }
        $pipeline = new Pipeline($this->file, $ref[2]);
        $ref[2] = $pipeline;

        return $pipeline;
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
        foreach ($this->pipelines as $id => $reference) {
            if ($pipeline === $reference[2]) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Searches a reference
     *
     * @param Reference $reference
     *
     * @throws ParseException
     * @throws \UnexpectedValueException
     * @throws InvalidArgumentException
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
     * @throws ParseException
     * @throws \UnexpectedValueException
     * @throws InvalidArgumentException
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
     * @throws \UnexpectedValueException
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
     * @return File
     */
    public function getFile()
    {
        if ($this->file instanceof File) {
            return $this->file;
        }

        throw new \BadMethodCallException('Unassociated node');
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

        $default = 'default';

        if (!isset($array[$default])) {
            return $references;
        }

        if (!is_array($array[$default])) {
            throw new ParseException("'${default}' requires a list of steps");
        }

        $references[$default] = array(
            $default,
            null,
            &$array[$default],
        );

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
     * @throws \UnexpectedValueException
     * @throws InvalidArgumentException
     *
     * @return null|string
     */
    private function searchIdByTypeReference($type, $reference)
    {
        if (!ReferenceTypes::isPatternSection($type)) {
            throw new InvalidArgumentException(sprintf('Invalid type %s', var_export($type, true)));
        }

        list($resolve, $result) = $this->searchIdNonPatternMatch($type, $reference);
        if ($resolve) {
            return  $result;
        }

        list($resolve, $result) = $this->searchIdPattern($type, $reference);

        # fall-back to default pipeline on no match
        return $resolve ? $result : $this->getIdDefault();
    }

    /**
     * @param null|string $section
     * @param $reference
     *
     * @return array
     */
    private function searchIdNonPatternMatch($section, $reference)
    {
        # section is n/a, fall back to default pipeline
        if (!isset($this->array[$section])) {
            return array(true, $this->getIdDefault());
        }

        # check for direct (non-pattern) match
        if (isset($this->array[$section][$reference])) {
            return array(true, "${section}/${reference}");
        }

        return array(false, null);
    }

    /**
     * get entry with largest pattern to match
     *
     * @param string $section
     * @param string $reference
     *
     * @return array
     */
    private function searchIdPattern($section, $reference)
    {
        $patterns = array_keys($this->array[$section]);

        $match = '';
        foreach ($patterns as $pattern) {
            $pattern = (string)$pattern;
            $result = Glob::match($pattern, $reference);
            if ($result && (strlen($pattern) > strlen($match))) {
                $match = $pattern;
            }
        }

        return array('' !== $match, "${section}/${match}");
    }
}
