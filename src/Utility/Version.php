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

    /**
     * @var string
     */
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
     * obtain version from git vcs
     *
     * the version is a composer accepted version (incl. additions from vcs format)
     *
     * @param string $dir
     * @param string $version [optional] provide version instead of obtaining from HEAD
     *
     * @return array($version, $error)
     */
    public static function gitComposerVersion($dir = '.', $version = null)
    {
        $status = 0;
        $version || $version = exec(
            sprintf(
                'git -C %s describe --tags --always --first-parent --dirty=+dirty --match \'[0-9].[0-9]*.[0-9]*\' 2>/dev/null',
                escapeshellarg($dir)
            ),
            $output,
            $status
        );
        unset($output);

        if (0 !== $status) {
            return array(null, sprintf('git-describe non-zero exit status: %d', $status));
        }

        if (!preg_match('~^(\d+\.\d+\.\d+)(?:[+-](.*))?$~D', $version, $matches)) {
            return array(null, sprintf('version format mismatch: %s', $version));
        }

        return array($matches[1] . (empty($matches[2]) ? '' : '+' . $matches[2]), null);
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
     * obtain the list of installed packages
     *
     * from composer installed.json, file format change log:
     *
     *  1.0.0: no installed.json
     *  1.6.4: list of packages
     *  2.0.0: installed struct w/ packages (and dev flag, 2.0.5 dev-packages-names list)
     *
     * @return array of installed packages, empty array if none
     */
    public function getInstalledPackages()
    {
        $installedJsonFile = $this->dir . '/../../../../composer/installed.json';
        if (false === $buffer = @file_get_contents($installedJsonFile)) {
            $buffer = 'null';
        }

        $installed = json_decode($buffer, false);

        return (array)(isset($installed->packages) ? $installed->packages : $installed);
    }

    /**
     * get git version from git repository
     *
     * @return null|string
     */
    public function getGitVersion()
    {
        list($version) = self::gitComposerVersion($this->dir);

        return $version;
    }
}
