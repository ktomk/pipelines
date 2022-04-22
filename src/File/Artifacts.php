<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

/**
 * Artifacts of a pipelines step
 *
 * @package Ktomk\Pipelines\File\File
 */
class Artifacts implements \Countable
{
    /**
     * @var array|string[]
     */
    private $paths;

    /**
     * Artifacts constructor.
     *
     * @param array|string[] $artifacts
     *
     * @throws ParseException
     */
    public function __construct(array $artifacts)
    {
        $this->parse($artifacts);
    }

    /**
     * @return array|string[]
     */
    public function getPaths()
    {
        return $this->paths;
    }

    #[\ReturnTypeWillChange]
    /**
     * @return int
     */
    public function count()
    {
        return count($this->paths);
    }

    /**
     *
     * @throws ParseException
     *
     * @return void
     */
    private function parse(array $artifacts)
    {
        // quick validation: if an "object" and it has the "paths" attribute, this is the list
        if (isset($artifacts['paths']) && is_array($artifacts['paths'])) {
            $artifacts = $artifacts['paths'];
        }

        // quick validation: requires a list of strings which must not be empty (can't in YAML anyway)
        if (!count($artifacts)) {
            throw new ParseException("'artifacts' requires a list");
        }

        foreach ($artifacts as $index => $string) {
            if (!is_string($string)) {
                throw new ParseException(sprintf(
                    "'artifacts' requires a list of paths",
                    $index
                ));
            }
        }

        $this->paths = $artifacts;
    }
}
