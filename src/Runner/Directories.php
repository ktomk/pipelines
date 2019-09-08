<?php

/*
 * pipelines
 *
 * Date: 10.06.18 22:13
 */

namespace Ktomk\Pipelines\Runner;

use InvalidArgumentException;

/**
 * Class Directories
 *
 * Provides common directories (as path-names) for the pipelines project
 *
 * @package Ktomk\Pipelines\Runner
 */
class Directories
{
    /**
     * @var string
     */
    private $project;

    /**
     * @var array
     */
    private $env;

    /**
     * Directories constructor.
     *
     * FIXME(tk) require $HOME in $env is kind of wrong, for the XDG_* family there are more fallback/s this class
     *           should adhere to - @see Directories::getBaseDirectory
     *
     * @param array $env
     * @param string $project directory
     */
    public function __construct(array $env, $project)
    {
        if (!basename($project)) {
            throw new InvalidArgumentException(sprintf('Invalid project directory "%s"', $project));
        }

        $this->project = $project;

        if (!isset($env['HOME'])) {
            /**
             * @throws \InvalidArgumentException
             */
            throw new InvalidArgumentException('No $HOME in environment');
        }

        $this->env = $env;
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
     * FIXME(tk) unused (only in tests!)
     *
     * @return string
     */
    public function getPipelineLocalDeploy()
    {
        return sprintf('%s/.pipelines/%s', $this->env['HOME'], $this->getName());
    }

    /**
     * Pipelines base directories
     *
     * In the style of the (here weakly referenced) XDG Base Directory specs.
     *
     * example: XDG_CONFIG_HOME is $HOME/.config
     *
     * TODO(tk): System-wide fall-backs in case $HOME is undefined/empty @see Directories::__construct
     * @link https://specifications.freedesktop.org/basedir-spec/basedir-spec-latest.html
     *
     * @param string $type name .
     * @param null|string $suffix
     * @return string
     */
    public function getBaseDirectory($type, $suffix = null)
    {
        static $paths = array(
            'XDG_DATA_HOME' => '$HOME/.local/share',
            'XDG_CACHE_HOME' => '$HOME/.cache',
        );

        if (!isset($paths[$type])) {
            throw new \InvalidArgumentException(sprintf(
                'Not a base directory: %s. Known base directories are: "%s"',
                var_export($type, true),
                implode('", "', array_keys($paths))
            ));
        }

        $buffer = null;
        if (isset($this->env[$type])) {
            $buffer = $this->env[$type];
        } else {
            $home = $this->env['HOME'];
            $buffer = substr_replace($paths[$type], $home, 0, strlen('$HOME'));
        }

        null === $suffix || $buffer .= '/' . $suffix;

        return $buffer;
    }
}
