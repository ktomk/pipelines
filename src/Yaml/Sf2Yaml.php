<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Yaml;

use Ktomk\Symfony\Component\Yaml\Exception\ParseException as SymfonyParseException;
use Ktomk\Symfony\Component\Yaml\Yaml as SymfonyYaml;

class Sf2Yaml implements ParserInterface
{
    /**
     * @return bool
     */
    public static function isAvailable()
    {
        return true;
    }

    /**
     * @param $path
     *
     * @throws ParseException
     *
     * @return null|array
     */
    public function parseFile($path)
    {
        return Yaml::fileDelegate($path, array($this, 'parseBuffer'));
    }

    /**
     * @param string $path
     *
     * @return null|array
     */
    public function tryParseFile($path)
    {
        return Yaml::fileDelegate($path, array($this, 'tryParseBuffer'));
    }

    /**
     * @param $buffer
     *
     * @throws ParseException
     *
     * @return array
     */
    public function parseBuffer($buffer)
    {
        try {
            $result = SymfonyYaml::parse($buffer);
        } catch (SymfonyParseException $ex) {
            throw new ParseException($ex->getMessage(), 0, $ex);
        }

        # catch sf2 invalid yaml parsing
        if (array(':') === $result) {
            throw new ParseException('Sf2Yaml invalid YAML parsing', 0);
        }

        return $result;
    }

    /**
     * @param string $buffer
     *
     * @return null|array
     */
    public function tryParseBuffer($buffer)
    {
        try {
            $result = $this->parseBuffer($buffer);
        } catch (ParseException $ex) {
            return null;
        }

        return $result;
    }
}
