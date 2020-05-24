<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Yaml;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

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
     * @param string $path
     *
     * @return null|array
     */
    public function parseFile($path)
    {
        return $this->parseBuffer(@file_get_contents($path));
    }

    /**
     * @param string $buffer
     *
     * @return null|array
     */
    public function parseBuffer($buffer)
    {
        try {
            $result = SymfonyYaml::parse($buffer);
        } catch (ParseException $ex) {
            return null;
        }

        # catch sf2 invalid yaml parsing
        if (array(':') === $result) {
            return null;
        }

        return $result;
    }
}
