<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Yaml;

use Ktomk\Pipelines\ErrorCatcher;

class LibYaml implements ParserInterface
{
    public static function isAvailable()
    {
        return extension_loaded('yaml') && function_exists('yaml_parse_file') && function_exists('yaml_parse');
    }

    /**
     * @param string $path
     * @return null|array
     */
    public function parseFile($path)
    {
        $error = ErrorCatcher::create();

        $result = yaml_parse_file($path, 0);

        $result = $error->end() ? null : $result;

        return !is_array($result)
            ? null
            # libyaml parser does aliases, remove any potential ones
            : json_decode(json_encode($result), true);
    }

    /**
     * @param string $buffer
     * @return null|array
     */
    public function parseBuffer($buffer)
    {
        $result = yaml_parse($buffer, 0);

        return !is_array($result) ? null : $result;
    }
}
