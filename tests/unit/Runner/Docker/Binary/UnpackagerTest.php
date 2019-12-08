<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker\Binary;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\LibFs;
use Ktomk\Pipelines\LibTmp;
use Ktomk\Pipelines\Runner\Directories;
use Ktomk\Pipelines\Runner\DirectoriesTest;
use Ktomk\Pipelines\TestCase;
use UnexpectedValueException;

/**
 * @covers \Ktomk\Pipelines\Runner\Docker\Binary\UnPackager
 */
class UnpackagerTest extends TestCase
{
    private $removeDirectories = array();

    /**
     * the pipelines test-suite ships with a miniature of a docker binary package
     * including a fake-named "binary" stub, see tests/data/package/docker-test-stub.
     *
     * @return array test package
     */
    public static function getTestPackage()
    {
        $testPackage = LibFs::normalizePathSegments(__DIR__ . '/../../../../data/package/docker-test-stub.tgz');

        return array(
            # 'name': name of the docker client represented by this package, used for binary name
            'name' => Repository::PKG_TEST,
            # 'uri': url/path to .tgz package
            'uri' => $testPackage,
            # 'basename': path inside the .tgz package of the docker binary
            'basename' => 'docker-test-stub',
            # 'sha256': hash of the .tgz package file
            'sha256' => '1af3681414daf27c3a1953ad0da7ac0a6c030152f488c83758864f4e27242608',
            # 'binary': basename of the extracted binary file
            'binary' => 'docker-test-stub',
            # 'binary_sha256': hash of the binary file
            'binary_sha256' => 'a38057f7c1cc10a51f6d8d08e0f8e78c1b869d024c8629f9096d522142e61bb7',
        );
    }

    protected function tearDown()
    {
        foreach ($this->removeDirectories as $dir) {
            LibFs::rmDir($dir);
        }

        parent::tearDown();
    }

    public function testCreation()
    {
        $directories = new Directories(Lib::env($_SERVER), '/dev/null');
        $packageDirectory = $directories->getBaseDirectory('XDG_CACHE_HOME', 'package-docker');
        $binariesDirectory = $directories->getBaseDirectory('XDG_DATA_HOME', 'static-docker');

        new UnPackager(
            new ExecTester($this),
            $packageDirectory,
            $binariesDirectory
        );
        $this->addToAssertionCount(1);
    }

    /**
     * Test static factory method
     */
    public function testCreationFromDirectories()
    {
        $directories = new Directories(Lib::env($_SERVER), '/dev/null');
        $unPackager = UnPackager::fromDirectories(new ExecTester($this), $directories);
        $this->assertInstanceOf('Ktomk\Pipelines\Runner\Docker\Binary\UnPackager', $unPackager);
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Docker\Binary\UnPackager::getLocalBinary()
     */
    public function testGetLocalBinary()
    {
        $testHome = $this->getTestHome();

        $exec = new Exec();
        $directories = new Directories(array('HOME' => $testHome), DirectoriesTest::getTestProject());

        $unPackager = UnPackager::fromDirectories($exec, $directories);
        $package = self::getTestPackage();
        $actual = $unPackager->getLocalBinary($package);

        $this->assertSame(
            sprintf(
                '%s/.local/share/pipelines/static-docker/%s.%s',
                $testHome,
                $package['name'],
                $package['binary_sha256']
            ),
            $actual
        );
    }

    /**
     * More an integration test for starters
     */
    public function testUnPackaging()
    {
        $testHome = $this->getTestHome();

        $exec = new Exec();
        $unPackager = UnPackager::fromDirectories(
            $exec,
            new Directories(array('HOME' => $testHome), DirectoriesTest::getTestProject())
        );

        $package = self::getTestPackage();
        $actual = $unPackager->getLocalBinary($package);
        $this->assertSame(
            sprintf(
                '%s/.local/share/pipelines/static-docker/%s.%s',
                $testHome,
                $package['name'],
                $package['binary_sha256']
            ),
            $actual
        );

        $prepare = $unPackager->preparePackage($package);

        LibFs::rm($prepare['prep']['pkg_local']);
        LibFs::rm($prepare['prep']['bin_local']);
        $unPackager->getLocalBinary($package);
        $this->addToAssertionCount(1);

        // test w/ removed tgz package in cache but existing binary
        // in store
        LibFs::rm($prepare['prep']['pkg_local']);
        $unPackager->getLocalBinary($package);
        $this->addToAssertionCount(1);
    }

    public function testUnpackagingDownloadCollisionOnTgzFileHash()
    {
        $testHome = $this->getTestHome();

        $exec = new Exec();
        $unpackager = UnPackager::fromDirectories(
            $exec,
            new Directories(array('HOME' => $testHome), DirectoriesTest::getTestProject())
        );

        $package = self::getTestPackage();
        $unpackager->getLocalBinary($package);
        $this->addToAssertionCount(1);

        $package['binary_sha256']  = '42';

        try {
            $unpackager->getLocalBinary($package);
            $this->fail('an expected exception was not thrown');
        } catch (UnexpectedValueException $ex) {
            $this->assertContains('Extraction collision: "', $ex->getMessage());
        }

        $prepare = $unpackager->preparePackage($package);
        LibFs::rm($prepare['prep']['bin_local']);
        $package['sha256'] = '42';

        try {
            $unpackager->getLocalBinary($package);
            $this->fail('an expected exception was not thrown');
        } catch (UnexpectedValueException $ex) {
            $this->assertContains('.tgz.1af', $ex->getMessage());
        }
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Docker\Binary\UnPackager::extractFromTgzFile()
     */
    public function testExtractFromTgzFile()
    {
        $tester = new ExecTester($this);
        $directories = new Directories(array('HOME' => '/alf-was-here'), ':');
        $unpacker = UnPackager::fromDirectories($tester, $directories);

        $pkg = self::getTestPackage();
        $tester->expect('pass', '~ tar$~');
        list(, $dest) = LibTmp::tmpFile();
        $unpacker->extractFromTgzFile($pkg['uri'], $pkg['binary'], $dest);
        $this->addToAssertionCount(1);

        $tester->expect('pass', '~ tar$~', 1);

        try {
            $unpacker->extractFromTgzFile($pkg['uri'], 'fake', $dest);
            passthru(sprintf('ls -al %s', Lib::quoteArg($dest)));
            $this->fail('an expected exception was not thrown');
        } catch (UnexpectedValueException $ex) {
            $this->assertSame('Nonzero tar exit status: 1', $ex->getMessage());
        }

        $this->setExpectedException('UnexpectedValueException', 'Not a readable file:');
        $unpacker->extractFromTgzFile($pkg['uri'] . '.fake', 'fake', $dest);
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Docker\Binary\UnPackager::verifyFileHash()
     */
    public function testVerifyFileHash()
    {
        $directories = new Directories(array('HOME' => '/home/ysl'), ':');
        $packageDirectory = $directories->getBaseDirectory('XDG_CACHE_HOME', 'package-docker');
        $binariesDirectory = $directories->getBaseDirectory('XDG_DATA_HOME', 'static-docker');

        $unpackager = new UnPackager(
            new ExecTester($this),
            $packageDirectory,
            $binariesDirectory
        );

        $package = self::getTestPackage();
        $unpackager->verifyFileHash($package['uri'], $package['sha256'], 'extra');
        $this->addToAssertionCount(1);

        // hash checksum mismatch throws exception
        try {
            $unpackager->verifyFileHash($package['uri'], 'fake-hash');
            $this->fail('an expected exception was not thrown');
        } catch (UnexpectedValueException $ex) {
            $this->assertContains('sha256 checksum mismatch: "fake-hash" for file "', $ex->getMessage());
        }

        // non-existent file throws exception
        try {
            $unpackager->verifyFileHash($package['uri'] . '.fake', 'fake-hash');
            $this->fail('an expected exception was not thrown');
        } catch (UnexpectedValueException $ex) {
            $this->assertContains('not a readable file:', $ex->getMessage());
        }
    }

    private function getTestHome()
    {
        return $this->removeDirectories[] = LibTmp::tmpDir('pipelines-test.');
    }
}
