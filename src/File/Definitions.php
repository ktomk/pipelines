<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File;

use Ktomk\Pipelines\File\Definitions\Services;
use Ktomk\Pipelines\Lib;

/**
 * Definitions node in pipelines file
 *
 * @package Ktomk\Pipelines\File
 */
class Definitions
{
    /**
     * @var array
     */
    private $array;

    /**
     * @var Definitions\Services
     */
    private $services;

    /**
     * Definitions constructor.
     *
     * @param array $array
     */
    public function __construct(array $array)
    {
        Lib::v($array['services'], array());

        $this->services = $this->parseDefinitionsServices($array['services']);

        $this->array = $array;
    }

    /**
     * @return Services
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * @param array $array
     *
     * @return Services
     */
    private function parseDefinitionsServices(array $array)
    {
        return new Services($array);
    }
}
