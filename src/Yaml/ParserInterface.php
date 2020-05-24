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
     * @return null|array
     */
    public function parseFile($path);

    /**
     * @param string $buffer
     *
     * @return null|array
     */
    public function parseBuffer($buffer);
}
