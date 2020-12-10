<?php

namespace Ktomk\Pipelines\Integration\Runner\Docker\Binary;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\LibFs;
use Ktomk\Pipelines\LibTmp;
use Ktomk\Pipelines\Runner\Directories;
use Ktomk\Pipelines\Runner\DirectoriesTest;
use Ktomk\Pipelines\Runner\Docker\Binary\UnPackager;
use Ktomk\Pipelines\TestCase;
use Ktomk\Pipelines\Value\SideEffect\DestructibleString;

/**
 * Class BinaryUnpackagerTest
 *
 * @coversNothing
 *
 * @see \Ktomk\Pipelines\Runner\Docker\Binary\UnPackager
 */
class UnpackagerTest extends TestCase
{
    /**
     * @var array
     */
    private $removeDirectories = array();

    /**
     * Integration test against a real users $HOME setting w/ a test-package
     *
     * Binaries are stored XDG_DATA_HOME based, e.g. ~/.local/share/pipelines/static-docker and are
     * suffixed with a dot "." followed by the files SHA256 hash (hex-encoded).
     * Downloads are cached XDG_CACHE_HOME based, e.g. ~/.cache/pipelines/package-docker and are
     * suffixed with a dot "." followed by the files SHA256 hash (hex-encoded).
     */
    public function testTestPackage()
    {
        $testDir = LibTmp::tmpDir('pipelines-test.');
        $this->removeDirectories[] = DestructibleString::rmDir($testDir);
        $testHome = LibFs::mkDir($testDir . '/home');

        $exec = new Exec();
        $directories = new Directories(
            Lib::env(array('HOME' => $testHome) + $_SERVER),
            DirectoriesTest::getTestProject()
        );
        $packageDirectory = $directories->getBaseDirectory('XDG_CACHE_HOME', 'package-docker');
        $binariesDirectory = $directories->getBaseDirectory('XDG_DATA_HOME', 'static-docker');

        $testPackage = \Ktomk\Pipelines\Runner\Docker\Binary\UnpackagerTest::getTestPackage();

        $unpackager = new UnPackager($exec, $packageDirectory, $binariesDirectory);

        // ensure cache / binary are removed from local store on first run
        $prepared = $unpackager->preparePackage($testPackage);
        LibFs::rm($prepared['prep']['pkg_local']);
        LibFs::rm($prepared['prep']['bin_local']);

        $actual = $unpackager->getLocalBinary($testPackage);
        self::assertIsString($actual);
        self::assertFileExists($actual);

        self::assertFileExists($prepared['prep']['pkg_local']);
        self::assertFileExists($prepared['prep']['bin_local']);

        // remove local binary to test if fetched from cache works
        LibFs::rm($prepared['prep']['bin_local']);
        $actual = $unpackager->getLocalBinary($testPackage);
        self::assertIsString($actual);
        self::assertFileExists($actual);
        self::assertFileExists($prepared['prep']['bin_local']);
    }
}
