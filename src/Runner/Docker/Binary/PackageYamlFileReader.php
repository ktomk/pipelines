<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker\Binary;

use Ktomk\Pipelines\LibFs;
use Ktomk\Pipelines\LibFsPath;
use Ktomk\Pipelines\Yaml\Yaml;

/**
 * Class BinaryPackageYmlReader
 *
 * Read binary package information from a YAML file
 */
class PackageYamlFileReader implements PackageInterface
{
    /**
     * @var string
     */
    private $file;

    /**
     * BinaryPackageYmlReader constructor.
     *
     * @param string $file path to package YAML file to read
     */
    public function __construct($file)
    {
        $this->file = LibFsPath::normalize($file);
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public function asPackageArray()
    {
        $path = $this->file;
        if (!LibFs::isReadableFile($path)) {
            throw new \InvalidArgumentException(
                sprintf("not a readable file: '%s'", $path)
            );
        }
        $package = (array)Yaml::buffer(file_get_contents($path));

        $this->resolveUri($package['uri']);

        return $package;
    }

    /**
     * resolve local path URI relative to document location
     *
     * @param null|string $uri
     *
     * @return void
     */
    private function resolveUri(&$uri)
    {
        if (null === $uri) {
            return;
        }

        // http/s is the only remote transport considered to be remote so far
        // as it is the only one used
        if (1 === preg_match('(^https?://)i', $uri)) {
            return;
        }

        if (LibFsPath::isAbsolute($uri)) {
            return;
        }

        // TODO(tk): maybe this whole part as it's resolving?
        $baseDir = dirname($this->file);
        $buffer = $baseDir . '/' . $uri;
        $uri = LibFsPath::normalize($buffer);
    }
}
