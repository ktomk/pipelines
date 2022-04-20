<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use InvalidArgumentException;
use Ktomk\Pipelines\Glob;

/**
 * Class PipelinesReferences
 *
 * Extract methods from Pipelines
 *
 * @package Ktomk\Pipelines\File
 */
abstract class PipelinesReferences extends Pipelines
{
    /**
     * @param Pipelines $pipelines
     * @param string $id
     *
     * @throws InvalidArgumentException
     * @throws ParseException
     *
     * @return null|Pipeline
     */
    protected static function byId(Pipelines $pipelines, $id)
    {
        if (!ReferenceTypes::isValidId($id)) {
            throw new InvalidArgumentException(sprintf("Invalid id '%s'", $id));
        }

        $file = $pipelines->file;
        if (!isset($pipelines->references[$id], $file)) {
            return null;
        }

        $ref = $pipelines->references[$id];
        if ($ref[2] instanceof Pipeline) {
            return $ref[2];
        }

        // bind to instance if yet an array
        if (!is_array($ref[2])) {
            throw new ParseException(sprintf('%s: named pipeline required', $id));
        }
        $pipeline = new Pipeline($file, $ref[2]);
        $ref[2] = $pipeline;

        return $pipeline;
    }

    /**
     * returns the id of the default pipeline in file or null if there is none
     *
     * @param Pipelines $pipelines
     *
     * @return null|string
     */
    protected static function idDefault(Pipelines $pipelines)
    {
        $id = ReferenceTypes::SEG_DEFAULT;

        if (!isset($pipelines->references[$id])) {
            return null;
        }

        return $id;
    }

    /**
     * get id of a pipeline
     *
     * @param Pipelines $pipelines
     * @param Pipeline $pipeline
     *
     * @return null|string
     */
    protected static function id(Pipelines $pipelines, Pipeline $pipeline)
    {
        foreach ($pipelines->references as $id => $reference) {
            if ($pipeline === $reference[2]) {
                return $id;
            }
        }

        return null;
    }

    /**
     * @param Pipelines $pipelines
     * @param null|string $type
     * @param null|string $reference
     *
     * @throws InvalidArgumentException
     *
     * @return null|string
     */
    protected static function idByTypeReference(Pipelines $pipelines, $type, $reference)
    {
        if (!ReferenceTypes::isPatternSection($type)) {
            throw new InvalidArgumentException(sprintf('Invalid type %s', var_export($type, true)));
        }

        list($resolve, $result) = self::idNonPatternMatch($pipelines, $type, $reference);
        if ($resolve) {
            return $result;
        }

        list($resolve, $result) = self::idPattern($pipelines, $type, (string)$reference);

        # fall-back to default pipeline on no match
        return $resolve ? $result : self::idDefault($pipelines);
    }

    /**
     * @param Pipelines $pipelines
     * @param null|string $section
     * @param null|string $reference
     *
     * @return array
     */
    private static function idNonPatternMatch(Pipelines $pipelines, $section, $reference)
    {
        # section is n/a, fall back to default pipeline
        if (!isset($pipelines->array[$section])) {
            return array(true, $pipelines->getIdDefault());
        }

        # check for direct (non-pattern) match
        if (isset($pipelines->array[$section][$reference])) {
            return array(true, "${section}/${reference}");
        }

        return array(false, null);
    }

    /**
     * get entry with largest pattern to match
     *
     * @param Pipelines $pipelines
     * @param string $section
     * @param string $reference
     *
     * @return array
     */
    private static function idPattern(Pipelines $pipelines, $section, $reference)
    {
        $patterns = array_keys($pipelines->array[$section]);

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
