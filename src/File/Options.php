<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\Lib;

/**
 * Definitions node in pipelines file
 *
 * @package Ktomk\Pipelines\File
 */
class Options
{
    /**
     * @var array
     */
    private $array;

    /**
     * Definitions constructor.
     *
     * @param array $array
     */
    public function __construct(array $array)
    {
        Lib::v($array['docker'], false);

        $this->array = $this->parseOptions($array);
    }

    /**
     * @return bool
     */
    public function getDocker()
    {
        return (bool)$this->array['docker'];
    }

    /**
     * @param array $array
     *
     * @return array
     */
    private function parseOptions(array $array)
    {
        if (!is_bool($array['docker'])) {
            throw new ParseException(sprintf("global option 'docker' should be a boolean, it is currently defined %s", gettype($array['docker'])));
        }

        return $array;
    }
}
