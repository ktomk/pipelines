<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker;

/**
 * Class ArgsBuilder
 *
 * Utility class to build command line arguments for hashmaps, e.g.
 * for environment variables (-e, --env) or labels (-l, --label).
 *
 * @package Ktomk\Pipelines\Runner\Docker
 */
class ArgsBuilder
{
    /**
     * @param string $option
     * @param string[] $list
     *
     * @return string[]
     */
    public static function optList($option, array $list)
    {
        $args = array();
        foreach ($list as $value) {
            $args[] = $option;
            $args[] = $value;
        }

        return $args;
    }

    /**
     * Multiple options from key/value map
     *
     * --option key=value --option key ...
     *
     * @param string $option  "-e" or "-l" typically for Docker binary
     * @param array $map
     * @param bool $dropNullEntries
     *
     * @return string[]  f options (from $option) and values, ['-e', 'val1', '-e', 'val2', ...]
     *
     * @see \Ktomk\Pipelines\Runner\Env::createArgVarDefinitions
     */
    public static function optMap($option, array $map, $dropNullEntries = false)
    {
        return self::optList($option, self::mapKeyValues($map, $dropNullEntries));
    }

    /**
     * @param array $map
     * @param bool $dropNullEntries
     *
     * @return string[]
     */
    public static function mapKeyValues(array $map, $dropNullEntries = false)
    {
        $array = array();

        foreach ($map as $key => $value) {
            if (isset($value)) {
                $array[] = sprintf('%s=%s', $key, $value);

                continue;
            }

            $dropNullEntries || $array[] = (string)$key;
        }

        return $array;
    }
}
