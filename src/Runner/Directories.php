<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use InvalidArgumentException;
use Ktomk\Pipelines\LibFs;
use Ktomk\Pipelines\Project;
use Ktomk\Pipelines\Utility\App;

/**
 * Pipelines directories.
 *
 * Provides common directories (as path-names) for the pipelines project,
 * making use of environment parameters like HOME and some from the
 * XDG Base Directory Specification (XDGBDS).
 *
 * @link https://specifications.freedesktop.org/basedir-spec/basedir-spec-latest.html
 * @link https://wiki.debian.org/XDGBaseDirectorySpecification
 * @link https://wiki.archlinux.org/index.php/XDG_Base_Directory
 *
 * @package Ktomk\Pipelines\Runner
 */
class Directories
{
    /**
     * @var array environment parameters
     */
    private $env;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var string name of the utility to have directories for (XDG)
     */
    private $utility;

    /**
     * @var array|string[]
     */
    private static $baseDirectory = array(
        'XDG_DATA_HOME' => '$HOME/.local/share',
        'XDG_CACHE_HOME' => '$HOME/.cache',
        'XDG_CONFIG_HOME' => '$HOME/.config',
    );

    /**
     * Directories ctor
     *
     * @param array|string[] $env
     * @param Project $project project
     * @param string $utility [optional] name, defaults to "pipelines"
     *
     * @throws InvalidArgumentException
     *
     * @see Directories::getBaseDirectory
     */
    public function __construct(array $env, Project $project, $utility = null)
    {
        if (!isset($env['HOME']) || '' === $env['HOME']) {
            throw new InvalidArgumentException('$HOME unset or empty');
        }

        null === $utility && $utility = App::UTILITY_NAME;
        if (!LibFs::isPortableFilename($utility)) {
            throw new InvalidArgumentException(sprintf('Not a portable utility name: "%s"', $utility));
        }

        $this->env = $env;
        $this->project = $project;
        $this->utility = $utility;
    }

    /**
     * basename of the project directory
     *
     * @return string
     */
    public function getName()
    {
        return (string)$this->project;
    }

    /**
     * Get project directory.
     *
     * project is the base-folder (root-folder) of the project which
     * is run by pipelines.
     *
     * @return string
     */
    public function getProjectDirectory()
    {
        return $this->project->getPath();
    }

    /**
     * FIXME(tk) unused (only in tests!) - reason: yet undecided what / when
     * to put the local deploy stuff in there. needs XDGBDS reading and
     * some more considerations as more likely more data is needed to store
     * in the file-system. But better use XDGDBS here.
     *
     * @return string
     */
    public function getPipelineLocalDeploy()
    {
        return sprintf('%s/.%s/%s', $this->env['HOME'], $this->utility, $this->getName());
    }

    /**
     * Pipelines base directories
     *
     * In the style of the (here weakly referenced) XDG Base Directory specs.
     *
     * Examples:
     *
     *      XDG_DATA_HOME is $HOME/.local/share if unset or empty
     *      XDG_CACHE_HOME is $HOME/.cache if unset or empty
     *      XDG_CONFIG_HOME is $HOME/.config if unset or empty
     *
     * @link https://specifications.freedesktop.org/basedir-spec/basedir-spec-latest.html
     * @link https://wiki.debian.org/XDGBaseDirectorySpecification
     * @link https://wiki.archlinux.org/index.php/XDG_Base_Directory
     *
     * @param string $type name XDG_DATA_HOME / XDG_CACHE_HOME / XDG_CONFIG_HOME
     * @param null|string $suffix
     *
     * @return string
     */
    public function getBaseDirectory($type, $suffix = null)
    {
        $paths = self::$baseDirectory;

        $this->validateBaseDirectory($type);

        if (isset($this->env[$type])) {
            $buffer = $this->env[$type];
        } else {
            $home = $this->env['HOME'];
            $buffer = substr_replace($paths[$type], $home, 0, strlen('$HOME'));
        }

        $buffer .= '/' . $this->utility;

        null === $suffix || $buffer .= '/' . $suffix;

        return $buffer;
    }

    /**
     * @param string $xdgName
     *
     * @return void
     */
    private function validateBaseDirectory($xdgName)
    {
        $paths = self::$baseDirectory;

        if (!isset($paths[$xdgName])) {
            throw new InvalidArgumentException(sprintf(
                'Not a base directory: %s. Known base directories are: "%s"',
                var_export($xdgName, true),
                implode('", "', array_keys($paths))
            ));
        }
    }
}
