<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Yaml;

use Ktomk\Pipelines\ErrorCatcher;
use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\LibFs;
use Spyc as MustangostangSpyc;

class Spyc implements ParserInterface
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
     * @return null|array
     */
    public function parseFile($path)
    {
        $fsIsStreamUri = LibFs::isStreamUri($path);
        if (!$fsIsStreamUri && !LibFs::isReadableFile($path)) {
            return null;
        }

        $error = ErrorCatcher::create();

        if ($fsIsStreamUri) {
            $path = file_get_contents($path);
        }

        $array = MustangostangSpyc::YAMLLoad($path);

        if ($error->end() || !is_array($array) || array() === $array) {
            return null;
        }

        $keys = array_keys($array);
        if ($keys === range(0, count($array) - 1, 1)) {
            return null;
        }

        return $array;
    }

    /**
     * @param string $buffer
     * @return null|array
     */
    public function parseBuffer($buffer)
    {
        $error = ErrorCatcher::create();

        $array = MustangostangSpyc::YAMLLoadString($buffer);

        return $error->end() ? null : $array;
    }
}
