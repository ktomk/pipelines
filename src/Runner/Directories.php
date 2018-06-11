<?php

/*
 * pipelines
 *
 * Date: 10.06.18 22:13
 */

namespace Ktomk\Pipelines\Runner;

use InvalidArgumentException;

class Directories
{
    /**
     * @var string
     */
    private $project;

    /**
     * @var array
     */
    private $server;

    /**
     * Directories constructor.
     *
     * @param array $server
     * @param string $project directory
     */
    public function __construct(array $server, $project)
    {
        if (!basename($project)) {
            throw new InvalidArgumentException(sprintf('Invalid project directory "%s"', $project));
        }

        $this->project = $project;

        if (!isset($server['HOME'])) {
            throw new InvalidArgumentException('Server must contain a "HOME" entry');
        }

        $this->server = $server;
    }

    /**
     * basename of the project directory
     *
     * @return string
     */
    public function getName()
    {
        return basename($this->project);
    }

    /**
     * @return string
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return string
     */
    public function getPipelineLocalDeploy()
    {
        return sprintf('%s/.pipelines/%s', $this->server['HOME'], $this->getName());
    }
}
