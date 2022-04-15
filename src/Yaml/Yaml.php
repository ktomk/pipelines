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
     * @throws ParseException
     * @throws \InvalidArgumentException
     *
     * @return null|array
     */
    public static function file($file)
    {
        $path = '-' === $file ? 'php://stdin' : $file;

        $path = LibFsStream::fdToPhp($path);

        if (!LibFsStream::isReadable($path)) {
            throw new \InvalidArgumentException(
                sprintf("not a readable file: '%s'", $file)
            );
        }

        return self::parser()->parseFile($path);
    }

    /**
     * @param string $file
     *
     * @throws \InvalidArgumentException
     *
     * @return null|array
     */
    public static function tryFile($file)
    {
        try {
            return self::file($file);
        } catch (ParseException $ex) {
            return null;
        }
    }

    /**
     * error-free (suppressed) file_get_contents()
     *
     * available for implementations aligned with {@see Yaml::file()}
     * that already checks readability before getting the contents.
     *
     * fall-back on file-reading error is null.
     *
     * @param string $path
     * @param callable $parseBuffer function(string $buffer): array|null
     *
     * @return null|array
     */
    public static function fileDelegate($path, $parseBuffer)
    {
        $buffer = @file_get_contents($path);

        return false === $buffer ? null : call_user_func($parseBuffer, $buffer);
    }

    /**
     * @param string $buffer
     *
     * @return null|array
     */
    public static function buffer($buffer)
    {
        return self::parser()->tryParseBuffer($buffer);
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
