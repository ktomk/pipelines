<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Yaml;

use Ktomk\Pipelines\LibFsStream;

class Yaml
{
    /**
     * @var array of Yaml parser classes
     *
     * @see ParserInterface
     * @see Yaml::parser()
     */
    public static $classes = array();

    /**
     * @param string $file
     *
     * @throws \InvalidArgumentException
     *
     * @return null|array on error
     */
    public static function file($file)
    {
        $path = '-' === $file ? 'php://stdin' : $file;

        /* @link https://bugs.php.net/bug.php?id=53465 */
        $path = preg_replace('(^/(?:proc/self|dev)/(fd/\d+))', 'php://\1', $path);

        if (!LibFsStream::isReadable($path)) {
            throw new \InvalidArgumentException(
                sprintf("not a readable file: '%s'", $file)
            );
        }

        return self::parser()->parseFile($path);
    }

    /**
     * @param string $buffer
     *
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
        $classes = self::$classes ?: array(
            'Ktomk\Pipelines\Yaml\LibYaml',
            'Ktomk\Pipelines\Yaml\Sf2Yaml',
        );

        foreach ($classes as $class) {
            if (
                class_exists($class)
                && is_subclass_of($class, 'Ktomk\Pipelines\Yaml\ParserInterface')
                && $class::isAvailable()
            ) {
                return new $class();
            }
        }

        throw new \BadMethodCallException(sprintf('No YAML parser available'));
    }
}
