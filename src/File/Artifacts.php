<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

/**
 * Artifacts of a pipelines step
 *
 * @package Ktomk\Pipelines\File
 */
class Artifacts
{
    /**
     * @var array|string[]
     */
    private $artifacts;

    /**
     * Artifacts constructor.
     *
     * @param array|string[] $artifacts
     */
    public function __construct($artifacts)
    {
        $this->parse($artifacts);
    }

    /**
     * @return array|string[]
     */
    public function getPatterns()
    {
        return $this->artifacts;
    }

    /**
     * @param array|string[] $artifacts
     */
    private function parse($artifacts)
    {
        // quick validation: requires a list of strings
        if (!is_array($artifacts) || !count($artifacts)) {
            ParseException::__("'artifacts' requires a list");
        }

        foreach ($artifacts as $index => $string) {
            if (!is_string($string)) {
                ParseException::__(sprintf(
                    "'artifacts' requires a list of strings, #%d is not a string",
                    $index
                ));
            }
        }

        $this->artifacts = $artifacts;
    }
}
