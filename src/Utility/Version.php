<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

/**
 * Utility class to obtain version of a PHP based CLI utility including but not
 * limited to a Git project context
 *
 * @package Ktomk\Pipelines\Utility
 */
class Version
{
    /**
     * @var string
     */
    private $dir;

    /**
     * @var string
     */
    private $placeholder;
    private $version;

    /**
     * resolve version in case it is still a placeholder @.@.@,
     * e.g. when a source installation (composer package or git
     * clone)
     *
     * @param string $version
     *
     * @return string resolved version
     */
    public static function resolve($version)
    {
        $subject = new self($version);

        return $subject->resolveSourceVersion();
    }

    /**
     * Version constructor.
     *
     * @param string $version input
     * @param string $placeholder
     * @param string $dir [optional]
     */
    public function __construct($version, $placeholder = '@.@.@', $dir = null)
    {
        $this->version = $version;
        $this->placeholder = $placeholder;
        $this->dir = null === $dir ? __DIR__ : $dir;
    }

    /**
     * obtain utility version for the installation / source
     * in use.
     *
     * @return string
     */
    public function resolveSourceVersion()
    {
        // as build version
        if (null !== $buildVersion = $this->getBuildVersion()) {
            return $buildVersion;
        }

        // as composer package
        if (null !== $packageVersion = $this->getPackageVersion()) {
            return $packageVersion;
        }

        // as git repository
        if (null !== $gitVersion = $this->getGitVersion()) {
            return $gitVersion;
        }

        return $this->version; // @codeCoverageIgnore
    }

    /**
     * is version from build?
     *
     * @return bool
     */
    public function isBuildVersion()
    {
        return $this->placeholder !== $this->version;
    }

    /**
     * @return null|string version or null if there is no build version
     */
    public function getBuildVersion()
    {
        if ($this->isBuildVersion()) {
            return $this->version;
        }

        return null;
    }

    /**
     * get package version from composer/installed.json.
     *
     * this is possible if pipelines is required as a composer package.
     *
     * @return null|string version or null if not required as composer package
     */
    public function getPackageVersion()
    {
        foreach ($this->getInstalledPackages() as $package) {
            if (!isset($package->name) || 'ktomk/pipelines' !== $package->name) {
                continue;
            }
            if (!isset($package->version)) {
                break;
            }

            return $package->version . '-composer';
        }

        return null;
    }

    /**
     * @return array of installed packages, empty array if none
     */
    public function getInstalledPackages()
    {
        $installedJsonFile = $this->dir . '/../../../../composer/installed.json';
        if (false === $buffer = @file_get_contents($installedJsonFile)) {
            $buffer = 'null';
        }

        return (array)json_decode($buffer);
    }

    /**
     * get git version from git repository
     *
     * @return null|string
     */
    public function getGitVersion()
    {
        $buffer = rtrim(exec(sprintf(
            'cd %s 2>/dev/null && echo "$(git describe --tags --always --first-parent 2>/dev/null)$(git diff-index --quiet HEAD -- 2>/dev/null || echo +)"',
            escapeshellarg($this->dir)
        )));

        if (false === in_array($buffer, array('+', ''), true)) {
            return $buffer;
        }

        return null;
    }
}
