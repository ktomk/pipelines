<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Pipeline;

use Ktomk\Pipelines\File\ParseException;

final class StepCondition
{
    /**
     * @var array pipeline definition
     */
    private $array;

    /**
     * @param array<string>
     */
    private $includePaths;

    /**
     * @param array $definition
     */
    public function __construct(array $definition)
    {
        $this->parseCondition($definition);
    }

    /**
     * @return array<string>
     */
    public function getIncludePaths()
    {
        return $this->includePaths ?: array();
    }

    private function parseCondition(array $definition)
    {
        $this->array = array();

        if (!isset($definition['changesets']) || !is_array($definition['changesets'])) {
            throw new ParseException('Condition with no "changesets"');
        }
        $changeSets = $definition['changesets'];
        if (!isset($changeSets['includePaths']) || !is_array($changeSets['includePaths'])) {
            throw new ParseException('Condition "changesets" with no "includePaths"');
        }
        $includePaths = $changeSets['includePaths'];
        if (1 > count($includePaths)) {
            throw new ParseException('Condition "changesets" "includePaths" must not be empty');
        }

        $this->parseIncludePaths($includePaths);
    }

    private function parseIncludePaths(array $includePaths)
    {
        $count = 0;
        foreach ($includePaths as $index => $path) {
            if ($index !== $count) {
                throw new ParseException('Condition "changesets" "includePaths" must be a list');
            }
            if (!is_string($path)) {
                throw new ParseException(
                    sprintf(
                        'Condition "changesets" "includePaths" must be string at index #%d, got %s (%s)',
                        $index,
                        gettype($path),
                        (string)$path
                    )
                );
            }
            if ('' === $path) {
                throw new ParseException(
                    sprintf(
                        'Condition "changesets" "includePaths" empty path at index #%d',
                        $index
                    )
                );
            }
            $this->includePaths[$count] = $path;
            $count++;
        }
    }
}
