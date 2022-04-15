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
        return extension_loaded('yaml') && function_exists('yaml_parse');
    }

    /**
     * @param string $path
     *
     * @throws ParseException
     *
     * @return null|array
     */
    public function parseFile($path)
    {
        return Yaml::fileDelegate($path, array($this, 'parseBuffer'));
    }

    /**
     * @param string $path
     *
     * @return null|array
     */
    public function tryParseFile($path)
    {
        return Yaml::fileDelegate($path, array($this, 'tryParseBuffer'));
    }

    /**
     * @param string $buffer
     *
     * @throws ParseException
     *
     * @return array
     */
    public function parseBuffer($buffer)
    {
        if (!function_exists('yaml_parse')) {
            // @codeCoverageIgnoreStart
            throw new \BadMethodCallException('LibYaml based parsing n/a, is the PHP extension loaded?');
            // @codeCoverageIgnoreEnd
        }

        $error = ErrorCatcher::create();
        $result = yaml_parse($buffer, 0);
        $result = $error->end() ? null : $result;
        if (null === $result && null !== $message = $error->getLastErrorMessage()) {
            throw new ParseException($message);
        }
        if (!is_array($result)) {
            throw new ParseException('LibYaml invalid YAML parsing');
        }

        # ext-yaml parser does aliases, remove any potential ones
        return json_decode(json_encode($result), true);
    }

    /**
     * @param string $buffer
     *
     * @return null|array
     */
    public function tryParseBuffer($buffer)
    {
        try {
            $result = $this->parseBuffer($buffer);
        } catch (ParseException $ex) {
            return null;
        }

        return $result;
    }
}
