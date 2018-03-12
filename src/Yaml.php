<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Spyc;

class Yaml
{
    /**
     * @param $path
     * @throws \InvalidArgumentException
     * @return null|array on error
     */
    public static function file($path)
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \InvalidArgumentException(
                sprintf("not a readable file: '%s'", $path)
            );
        }

        $last = error_get_last();
        $level = error_reporting();
        error_reporting(0);
        $array = Spyc::YAMLLoad($path);
        $error = error_get_last() !== $last;
        error_reporting($level);

        return $error ? null : $array;
    }

    public static function buffer($buffer)
    {
        return Spyc::YAMLLoadString($buffer);
    }
}
