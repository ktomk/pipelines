<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Yaml;

use Ktomk\Pipelines\ErrorCatcher;

class LibYaml implements ParserInterface
{
    /**
     * @return bool
     */
    public static function isAvailable()
    {
        return extension_loaded('yaml') && function_exists('yaml_parse_file') && function_exists('yaml_parse');
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
        $error = ErrorCatcher::create();
        $result = yaml_parse($buffer, 0);
        $result = $error->end() ? null : $result;

        return !is_array($result)
            ? null
            # ext-yaml parser does aliases, remove any potential ones
            : json_decode(json_encode($result), true);
    }
}
