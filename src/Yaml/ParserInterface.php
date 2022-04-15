<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Yaml;

interface ParserInterface
{
    /**
     * @return bool
     */
    public static function isAvailable();

    /**
     * @param string $path
     *
     * @throws ParseException
     *
     * @return null|array
     */
    public function parseFile($path);

    /**
     * @param string $path
     *
     * @return null|array
     */
    public function tryParseFile($path);

    /**
     * @param string $buffer
     *
     * @throws ParseException
     *
     * @return array
     */
    public function parseBuffer($buffer);

    /**
     * @param string $buffer
     *
     * @return null|array
     */
    public function tryParseBuffer($buffer);
}
