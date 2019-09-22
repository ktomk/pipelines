<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Yaml;

use Ktomk\Pipelines\LibFs;

class Yaml
{
    /**
     * @param $path
     * @throws \InvalidArgumentException
     * @return null|array on error
     */
    public static function file($path)
    {
        if (!LibFs::isReadableFile($path)) {
            throw new \InvalidArgumentException(
                sprintf("not a readable file: '%s'", $path)
            );
        }

        return self::parser()->parseFile($path);
    }

    /**
     * @param string $buffer
     * @return null|array
     */
    public static function buffer($buffer)
    {
        return self::parser()->parseBuffer($buffer);
    }

    /**
     * @return ParserInterface
     */
    private static function parser()
    {
        $classes = array(
            'Ktomk\Pipelines\Yaml\LibYaml',
            'Ktomk\Pipelines\Yaml\Sf2Yaml',
        );

        $class = null;

        foreach ($classes as $class) {
            if ($class::isAvailable()) {
                break;
            }
        }

        return new $class;
    }
}
