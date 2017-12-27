<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Spyc;

class Yaml
{
    public static function file($path)
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \InvalidArgumentException(
                sprintf("not a readable file: '%s'", $path)
            );
        }

        $array = Spyc::YAMLLoad($path);

        return $array;
    }

    public static function buffer($buffer)
    {
        $array = Spyc::YAMLLoadString($buffer);

        return $array;
    }
}
