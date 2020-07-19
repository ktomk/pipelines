<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Containers;

use Ktomk\Pipelines\Runner\Runner;
use Ktomk\Pipelines\Utility\App as UtilityApp;
use Ktomk\Pipelines\Value\Prefix;

/**
 * Class LabelsBuilder
 *
 * Build labels for containers
 *
 * @package Ktomk\Pipelines\Runner\Containers
 */
class LabelsBuilder
{
    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $project;

    /**
     * @var string
     */
    private $projectDirectory;

    /**
     * @var string
     */
    private $role;

    /**
     * @return self
     */
    public static function createFromRunner(Runner $runner)
    {
        $builder = new self();

        $builder
            ->setPrefix($runner->getPrefix())
            ->setProject($runner->getProject())
            ->setProjectDirectory($runner->getProjectDirectory());

        return $builder;
    }

    /**
     * LabelsBuilder constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param string $role
     *
     * @return LabelsBuilder
     */
    public function setRole($role)
    {
        $this->role = Role::verify($role);

        return $this;
    }

    /**
     * @return string[]
     */
    public function toArray()
    {
        $labels = array();

        $labels['pipelines.prefix'] = UtilityApp::UTILITY_NAME === $this->prefix ? '' : $this->prefix;
        $labels['pipelines.role'] = $this->role;
        $labels['pipelines.project.name'] = $this->project;
        $labels['pipelines.project.path'] = $this->projectDirectory;

        return $labels;
    }

    /**
     * @param string $prefix
     *
     * @return LabelsBuilder
     */
    public function setPrefix($prefix)
    {
        $this->prefix = Prefix::verify($prefix);

        return $this;
    }

    /**
     * @param string $project
     *
     * @return LabelsBuilder
     */
    public function setProject($project)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @param string $projectDirectory
     *
     * @return LabelsBuilder
     */
    public function setProjectDirectory($projectDirectory)
    {
        $this->projectDirectory = $projectDirectory;

        return $this;
    }
}
