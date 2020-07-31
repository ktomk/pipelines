<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Definitions;

use Ktomk\Pipelines\File\ParseException;

class Caches
{
    /**
     * @var array
     */
    private $predefined = array();

    /**
     * @var array
     */
    private $map = array();

    /**
     * Caches constructor.
     *
     * @param array $array
     */
    public function __construct(array $array)
    {
        $this->predefined = array(
            'composer' => '~/.composer/cache',
            'dotnetcore' => '~/.nuget/packages',
            'gradle' => '~/.gradle/caches',
            'ivy2' => '~/.ivy2/cache',
            'maven' => '~/.m2/repository',
            'node' => 'node_modules',
            'pip' => '~/.cache/pip',
            'sbt' => '~/.sbt',
        );

        $this->parse($array);
    }

    /**
     * @param string $name
     *
     * @return null|string|true path of custom or default cache definition, true for internal caches and null for no definition
     */
    public function getByName($name)
    {
        // docker cache is system-wide in pipelines
        if ('docker' === $name) {
            return true;
        }

        $mapMode = $this->map + $this->predefined;

        if (isset($mapMode[$name])) {
            return $mapMode[$name];
        }

        return null;
    }

    /**
     * @param array $names
     *
     * @return array cache map
     */
    public function getByNames(array $names)
    {
        $reservoir = array();

        $mapMode = $this->map + $this->predefined;
        unset($mapMode['docker']); // docker cache is system-wide in pipelines

        foreach ($names as $name) {
            if (!isset($mapMode[$name])) {
                continue;
            }
            $reservoir[$name] = $mapMode[$name];
            unset($mapMode[$name]);
        }

        return $reservoir;
    }

    /**
     * @param array $array
     *
     * @return void
     */
    private function parse(array $array)
    {
        foreach ($array as $name => $path) {
            if (!is_string($name)) {
                throw new ParseException("cache definition invalid cache name: ${name}");
            }

            if (null === $path) {
                throw new ParseException("cache '${name}' should be a string value (it is currently null or empty)");
            }

            if (is_bool($path)) {
                throw new ParseException("cache '${name}' should be a string (it is currently defined as a boolean)");
            }

            // Fixme(tk): more importantly is that $path is not array or object
        }

        $this->map = $array;
    }
}
