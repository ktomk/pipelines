<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

/**
 * Artifacts of a pipelines step
 *
 * @package Ktomk\Pipelines\File\File
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
     *
     * @throws ParseException
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
     *
     * @throws ParseException
     *
     * @return void
     */
    private function parse($artifacts)
    {
        // quick validation: requires a list of strings
        if (!count($artifacts)) {
            throw new ParseException("'artifacts' requires a list");
        }

        foreach ($artifacts as $index => $string) {
            if (!is_string($string)) {
                throw new ParseException(sprintf(
                    "'artifacts' requires a list of strings, #%d is not a string",
                    $index
                ));
            }
        }

        $this->artifacts = $artifacts;
    }
}
