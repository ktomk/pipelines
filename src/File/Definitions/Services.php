<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Definitions;

use Countable;
use Ktomk\Pipelines\File\ParseException;

/**
 * Class Services
 *
 * @package Ktomk\Pipelines\File\Definitions
 */
class Services implements Countable
{
    /**
     * @var array
     */
    private $array;

    /**
     * @var array|Service[]
     */
    private $services = array();

    /**
     * Services constructor.
     *
     * @param array $array
     */
    public function __construct(array $array)
    {
        $this->parseServices($array);

        $this->array = $array;
    }

    /**
     * @param string $serviceName
     *
     * @return null|Service
     */
    public function getByName($serviceName)
    {
        return isset($this->services[$serviceName]) ? $this->services[$serviceName] : null;
    }

    /**
     * get array of services by names
     *
     * if a service is not found it will not be returned
     *
     * @param string[] $serviceNames names of services to obtain
     *
     * @return Service[]
     */
    public function getByNames(array $serviceNames)
    {
        return array_intersect_key($this->services, array_flip($serviceNames));
    }

    #[\ReturnTypeWillChange]
    /**
     * @return int
     */
    public function count()
    {
        return count($this->services);
    }

    /**
     * @param array $array
     *
     * @return void
     */
    private function parseServices(array $array)
    {
        foreach ($array as $name => $service) {
            if (!is_string($name)) {
                throw new ParseException(sprintf('Invalid service definition name: %s', var_export($name, true)));
            }
            if (!is_array($service)) {
                throw new ParseException(sprintf('Invalid service definition "%s"', $name));
            }
            // docker service is internal, for pipelines no need here to handle it
            if ('docker' === $name) {
                continue;
            }
            $this->services[$name] = $this->parseNamedService($name, $service);
        }
    }

    /**
     * @param string $name
     * @param array $service
     *
     * @return Service
     */
    private function parseNamedService($name, array $service)
    {
        return new Service($name, $service);
    }
}
