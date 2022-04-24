<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker\Binary;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\LibFs;
use Ktomk\Pipelines\Runner\Directories;

/**
 * Class DockerBinaryUnpackager
 *
 * Knows how to handle a docker client binary package structure to provide
 * the docker client binary as pathname.
 *
 *   Binaries are packaged in the docker project below https://download.docker.com/linux/static/stable/...
 * in versioned .tgz files (e.g. x86_64/docker-19.03.1.tgz).
 *
 *   The docker binary is a single file inside such a .tgz file, which is getting extracted and then
 * is called the binary in the local store.
 *
 *   Both the download of the .tgz file as well as the extraction of the docker binary from it is
 * verified against a checksum each so that downloading and extracting can be verified as successful.
 *
 *   Binaries are stored in pipelines XDG_DATA_HOME in the static-docker folder, e.g.
 * ~/.local/share/pipelines/static-docker
 *
 *   Before downloading, it is checked if the .tgz package is already cached. The cache is in the
 * XDG_CACHE_HOME, e.g. ~/.cache/pipelines/package-docker
 *
 * @package Ktomk\Pipelines\Runner\Docker
 */
class UnPackager
{
    const BYTES_80MB = 83886080;

    /**
     * @var Exec
     */
    private $exec;

    /**
     * @var string
     */
    private $packageDirectory;

    /**
     * @var string
     */
    private $binariesDirectory;

    /**
     * Create binary unpackager based on default directories.
     *
     * @param Exec $exec
     * @param Directories $directories
     *
     * @return UnPackager
     */
    public static function fromDirectories(Exec $exec, Directories $directories)
    {
        $packageDirectory = $directories->getBaseDirectory('XDG_CACHE_HOME', 'package-docker');
        $binariesDirectory = $directories->getBaseDirectory('XDG_DATA_HOME', 'static-docker');

        return new self($exec, $packageDirectory, $binariesDirectory);
    }

    /**
     * BinaryUnPackager constructor.
     *
     * @param Exec $exec unpackager is using shell commands w/ tar for unpacking
     * @param string $packageDirectory where to store package downloads to (e.g. HTTP cache)
     * @param string $binariesDirectory where to store binaries to (can be kept ongoing to run pipelines)
     */
    public function __construct(Exec $exec, $packageDirectory, $binariesDirectory)
    {
        $this->exec = $exec;
        $this->packageDirectory = $packageDirectory;
        $this->binariesDirectory = $binariesDirectory;
    }

    /**
     * Get binary path from local store.
     *
     * @param array $package
     *
     * @return string
     */
    public function getLocalBinary(array $package)
    {
        $package = $this->preparePackage($package);

        $binLocal = $package['prep']['bin_local'];
        $pkgLocal = $package['prep']['pkg_local'];

        if (!LibFs::isReadableFile($binLocal) && !LibFs::isReadableFile($pkgLocal)) {
            $this->download($package);
        }

        $this->extract($package);

        $message = sprintf('Verify and rename or remove the file to get it downloaded again from %s', $package['uri']);
        $this->verifyFileHash($binLocal, $package['binary_sha256'], $message);

        return $binLocal;
    }

    /**
     * @param array $package
     *
     * @return array
     */
    public function preparePackage(array $package)
    {
        $cache = libFs::mkDir($this->packageDirectory, 0700);
        $share = libFs::mkDir($this->binariesDirectory, 0700);

        $pkgBase = sprintf('%s/%s', $cache, basename($package['uri']));
        $binBase = sprintf('%s/%s', $share, $package['name']);

        $package['prep']['cache'] = $cache;
        $package['prep']['pkg_base'] = $pkgBase;
        $package['prep']['pkg_local'] = sprintf('%s.%s', $pkgBase, $package['sha256']);
        $package['prep']['share'] = $share;
        $package['prep']['bin_base'] = $binBase;
        $package['prep']['bin_local'] = sprintf('%s.%s', $binBase, $package['binary_sha256']);

        return $package;
    }

    /**
     * @param string $tgz path to tar-gz (.tgz) file
     * @param string $path path in package to extract from
     * @param string $dest path to extract to
     *
     * @return void
     */
    public function extractFromTgzFile($tgz, $path, $dest)
    {
        if (!LibFs::isReadableFile($tgz)) {
            throw new \UnexpectedValueException(sprintf('Not a readable file: %s', $tgz));
        }

        LibFs::rm($dest);
        $status = $this->exec->pass(
            sprintf('> %s tar', Lib::quoteArg($dest)),
            array('-xOzf', $tgz, $path)
        );

        if (0 !== $status) {
            LibFs::rm($dest);

            throw new \UnexpectedValueException(sprintf('Nonzero tar exit status: %d', $status));
        }
    }

    /**
     * verify sha256 has of a file
     *
     * @param string $file
     * @param string $hash sha256 hash of the file
     * @param string $message [optional] additional error message information
     *
     * @return void
     */
    public function verifyFileHash($file, $hash, $message = '')
    {
        if (!LibFs::isReadableFile($file)) {
            throw new \UnexpectedValueException(sprintf('not a readable file: "%s"', $file));
        }

        $actual = hash_file('sha256', $file);
        if ($actual !== $hash) {
            $buffer = sprintf(
                'sha256 checksum mismatch: "%s" for file "%s".',
                $hash,
                $file
            );
            '' !== $message && $buffer .= ' ' . $message;

            throw new \UnexpectedValueException($buffer);
        }
    }

    /**
     * @param array $package
     *
     * @return void
     */
    private function download(array $package)
    {
        $base = $package['prep']['pkg_base'];

        // Download the package if not in the http-cache
        $src = fopen($package['uri'], 'rb');
        $dest = fopen(libFs::rm($base), 'wb');
        $src && $dest && stream_copy_to_stream($src, $dest, self::BYTES_80MB);
        $src && fclose($src);
        $dest && fclose($dest);

        $hash = hash_file('sha256', $base);
        $pkgLocalIn = sprintf('%s.%s', $base, $hash);
        if (LibFs::IsReadableFile($pkgLocalIn)) {
            throw new \UnexpectedValueException(sprintf('Download collision: %s', $pkgLocalIn));
        }
        LibFs::Rename($base, $pkgLocalIn);
    }

    /**
     * @param array $package
     *
     * @return void
     */
    private function extract(array $package)
    {
        $binLocal = $package['prep']['bin_local'];

        if (LibFs::isReadableFile($binLocal)) {
            $this->verifyFileHash($binLocal, $package['binary_sha256']);

            return;
        }

        $pkgLocal = $package['prep']['pkg_local'];
        $base = $package['prep']['bin_base'];

        $this->extractFromTgzFile($pkgLocal, $package['binary'], $base);

        $hash = hash_file('sha256', $base);
        $binLocal = sprintf('%s.%s', $base, $hash);
        if (LibFs::isReadableFile($binLocal)) {
            LibFs::rm($base);

            throw new \UnexpectedValueException(sprintf('Extraction collision: "%s"', $binLocal));
        }

        LibFs::Rename($base, $binLocal);
    }
}
