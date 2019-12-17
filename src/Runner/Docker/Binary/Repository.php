<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker\Binary;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\Runner\Directories;

/**
 * Docker Binary Repository
 *
 * Docker provides a static binary which is useful to have in containers when
 * in the container itself docker should be available (e.g. services: - docker).
 *
 * It "comes" out of this binary repository. In the background there is a
 * BinaryUnPackager taking care of network interaction and file verification
 * as it is technically possible to have different kind of static docker
 * binaries also from different packages.
 *
 * Also there is a YAML package file reader which can read package information
 * out of such files.
 *
 * @package Ktomk\Pipelines\Runner\Docker
 */
class Repository implements PackageInterface
{
    /**
     * common package names
     *
     * the test docker client is a fake / stub
     * the previous docker client was in use in the "self-install" example lib/pipelines/docker-client-install.sh
     * the integrate docker client is with #1019 --env-file pr which pipelines benefit from
     */
    const PKG_TEST = 'docker-42.42.1-binsh-test-stub';
    const PKG_PREVIOUS = 'docker-17.12.0-ce-linux-static-x86_64';
    const PKG_ATLBBCPP = 'docker-18.09.1-linux-static-x86_64';
    const PKG_INTEGRATE = 'docker-19.03.1-linux-static-x86_64';

    /**
     * @var Exec
     */
    private $exec;

    /**
     * @var null|array package definition data
     */
    private $package;

    /**
     * @var UnPackager
     */
    private $unPackager;

    /**
     * Static factory method.
     *
     * @param Exec $exec
     * @param Directories $directories (UnPackager dependency)
     * @return Repository
     */
    public static function create(Exec $exec, Directories $directories)
    {
        $unPackager = UnPackager::fromDirectories($exec, $directories);

        return new self($exec, array(), $unPackager);
    }

    /**
     * Repository constructor.
     *
     * @param Exec $exec
     * @param array $package [optional - on removal]
     * @param UnPackager $unPackager
     */
    public function __construct(Exec $exec, array $package, UnPackager $unPackager)
    {
        $this->exec = $exec;
        $this->package = $package;
        $this->unPackager = $unPackager;
    }

    /**
     * provision docker client into a running container
     *
     * install a binary as /usr/bin/docker and make it executable.
     * show the version.
     *
     * @param string $containerId
     * @param string $path to static docker client binary
     * @return array array(int $status, string $message) docker client binary version (docker --version) or error
     */
    public function containerProvisionDockerClientBinary($containerId, $path)
    {
        $status = $this->exec->capture(
            sprintf('2>&1 < %s docker', lib::quoteArg($path)),
            array('exec', '-i', $containerId, '/bin/sh', '-c', 'mkdir -p /usr/bin && cat - > /usr/bin/docker; chmod +x /usr/bin/docker; docker --version'),
            $out
        );

        return array($status, (string)$out);
    }

    /**
     * Resolve a binary package name in this repository
     *
     * @param string $packageName
     * @throws \InvalidArgumentException
     * @return Repository
     */
    public function resolve($packageName)
    {
        $ext = pathinfo($packageName, PATHINFO_EXTENSION);
        if ('yml' === $ext) {
            $file = $packageName;
        } else {
            $packageDir = __DIR__ . '/../../../../lib/package';
            $file = sprintf('%s/%s.yml', $packageDir, $packageName);
        }

        $reader = new PackageYamlFileReader($file);
        $this->package = $reader->asPackageArray();

        return $this;
    }

    /**
     * @param string $containerId
     * @throws \InvalidArgumentException
     * @return array array(int $status, string $message) docker client binary version (docker --version) or error
     */
    public function inject($containerId)
    {
        $package = $this->asPackageArray();
        $localBinary = $this->getLocalBinary($package);

        return $this->containerProvisionDockerClientBinary($containerId, $localBinary);
    }

    /**
     * Inject binary docker client package by name into container
     *
     * @param string $name
     * @param string $containerId
     */
    public function injectPackage($name, $containerId)
    {
        $this->resolve($name);
        $this->inject($containerId);
    }

    /**
     * Get binary path from local store.
     *
     * @param array $package
     * @return string
     */
    public function getLocalBinary(array $package)
    {
        return $this->unPackager->getLocalBinary($package);
    }

    /**
     * @inheritDoc
     * @throws \InvalidArgumentException
     */
    public function asPackageArray()
    {
        $package = $this->package;
        if (!$package) {
            $package = $this->resolve(self::PKG_INTEGRATE)->package;
        }

        return $package;
    }
}
