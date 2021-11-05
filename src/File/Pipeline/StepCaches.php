<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Pipeline;

use Ktomk\Pipelines\File\Definitions\Caches;
use Ktomk\Pipelines\File\Dom\FileNode;
use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\File\ParseException;

/**
 * Class StepCaches
 *
 * Caches entry in a step
 *
 * @package Ktomk\Pipelines\File\File
 */
class StepCaches implements FileNode, \IteratorAggregate
{
    /**
     * @var Step
     */
    private $step;

    /**
     * @var array
     * @psalm-var array<string, int>
     */
    private $caches;

    /**
     * StepCaches constructor.
     *
     * @param Step $step
     * @param null|array|mixed $caches
     *
     * @return void
     */
    public function __construct(Step $step, $caches)
    {
        $this->step = $step;

        // quick validation: script
        $parsed = $this->parseCaches($caches);

        $this->caches = array_flip($parsed);
    }

    /**
     * get step caches (as defined)
     *
     * @return array cache map
     */
    public function getDefinitions()
    {
        if (null === $file = $this->getFile()) {
            return array();
        }

        return $file->getDefinitions()->getCaches()->getByNames($this->getNames());
    }

    /**
     * Get all cache names of step
     *
     * @return array|string[]
     */
    public function getNames()
    {
        return array_keys($this->caches);
    }

    /**
     * @return null|File
     */
    public function getFile()
    {
        return $this->step->getFile();
    }

    #[\ReturnTypeWillChange]
    /**
     * @return \ArrayIterator|string[]
     * @psalm-return \ArrayIterator<array-key, string>
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->getDefinitions());
    }

    /**
     * parse caches
     *
     * @param null|array|mixed $caches
     *
     * @return string[]
     */
    private function parseCaches($caches)
    {
        if (!is_array($caches)) {
            throw new ParseException("'caches' requires a list of caches");
        }

        $reservoir = array();
        foreach ($caches as $cache) {
            if (!is_string($cache)) {
                throw new ParseException("'caches' cache name string expected");
            }

            '' === ($cache = trim($cache)) || $reservoir[] = $cache;

            $this->verifyCache($cache);
        }

        return $reservoir;
    }

    /**
     * @param string $cache
     *
     * @throws ParseException
     *
     * @return void
     */
    private function verifyCache($cache)
    {
        if ('' === $cache) {
            throw new ParseException("'caches' cache name must not be empty");
        }

        if (null === $this->getCacheDefinitionByName($cache)) {
            throw new ParseException(
                sprintf("cache '%s' must reference a custom or default cache definition", $cache)
            );
        }
    }

    /**
     * @param string $name
     *
     * @return null|string|true
     */
    private function getCacheDefinitionByName($name)
    {
        return $this->step->getFile()->getDefinitions()->getCaches()->getByName($name);
    }
}
