<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Integration\Runner\Docker\Binary;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\DestructibleString;
use Ktomk\Pipelines\File\Pipeline\Step;
use Ktomk\Pipelines\LibFs;
use Ktomk\Pipelines\LibTmp;
use Ktomk\Pipelines\Runner\Directories;
use Ktomk\Pipelines\Runner\DirectoriesTest;
use Ktomk\Pipelines\Runner\Docker\Binary\Repository;
use Ktomk\Pipelines\Runner\Env;
use Ktomk\Pipelines\Runner\Flags;
use Ktomk\Pipelines\Runner\Runner;
use Ktomk\Pipelines\Runner\RunOpts;
use Ktomk\Pipelines\TestCase;

/**
 * Class DockerBinaryTest
 *
 * @covers \Ktomk\Pipelines\Runner\Docker\Binary\Repository
 */
class RepositoryTest extends TestCase
{
    /**
     * @var array store destructible objects to test-case life-time
     */
    private $cleaners;

    public function testCreation()
    {
        $binary = Repository::create(new ExecTester($this), $this->createMock('Ktomk\Pipelines\Runner\Directories'));
        $this->assertInstanceOf('Ktomk\Pipelines\Runner\Docker\Binary\Repository', $binary);
    }

    /**
     * test injection but also take care that the test home directory is prepped with
     * the package downloads.
     *
     * downloads 60.3 MiB docker-19.03.1.tgz and untars 65.6 MiB docker binary from it
     */
    public function testInjection()
    {
        // TODO $homeDir = LibFs::tmpDir('pipelines-test-home.');
        // currently using project local directory to keep artifacts on local build, but
        // there should be the local test-package with the stub which works completely
        // offline.
        $homeDir = LibFs::normalizePathSegments(__DIR__ . '/../../../../../build/store/home');
        LibFs::mkDir($homeDir);

        $exec = new Exec();
        $repository = Repository::create($exec, new Directories(array('HOME' => $homeDir), DirectoriesTest::getTestProject()));
        $repository->resolve(Repository::PKG_INTEGRATE);
        $containerId = '424242-so-long-and-thanks-for-all-the-fish';
        list($status, $message) = $repository->inject($containerId);
        $this->assertSame(1, $status);
        $this->assertRegExp("~${containerId}~", $message);
        $this->addToAssertionCount(1);
    }

    /**
     * integration test w/ temporary home directory (works offline but needs a writeable /tmp directory)
     */
    public function testInjectIntegration()
    {
        $homeDir = DestructibleString::rmDir(LibTmp::tmpDir('pipelines-test-home.'));

        $directories = new Directories(array('HOME' => (string)$homeDir), 'bar');
        $repository = Repository::create(new Exec(), $directories);
        $repository->resolve(Repository::PKG_TEST);
        $actual = $repository->inject('42-bin-sh');
        $this->assertSame(array(1, "Error: No such container: 42-bin-sh\n"), $actual);
    }

    /**
     * NOTE: This test still requires to have the download untarred
     *
     * testInjection dependency creates build home and local docker binary to inject
     *
     * @depends testInjection
     * @covers \Ktomk\Pipelines\Runner\Runner
     */
    public function testRunnerWithDeploy()
    {
        $prefix = 'pipelines-integration-test';

        $homeDir = LibFs::normalizePathSegments(__DIR__ . '/../../../../../build/store/home');
        $project = LibTmp::tmpDir('pipelines-test-suite.');
        $this->cleaners[] = DestructibleString::rmDir($project);
        $directories = new Directories(array('HOME' => $homeDir), $project);
        $exec = new ExecTester($this);
        $env = new Env();
        $streams = new Streams(null, 'php://output');
        $runner = Runner::createEx(
            RunOpts::create($prefix, Repository::PKG_INTEGRATE),
            $directories,
            $exec,
            new Flags(),
            $env,
            $streams
        );

        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $file = $this->createMock('Ktomk\Pipelines\File\File');
        $image = $this->createMock('Ktomk\Pipelines\File\Image');
        $definitions = $this->createMock('Ktomk\Pipelines\File\Definitions');
        $services = $this->createMock('Ktomk\Pipelines\File\Definitions\Services');
        $definitions->method('getServices')->willReturn($services);

        $image->method('getProperties')->willReturn(array());
        $file->method('getImage')->willReturn($image);
        $pipeline->method('getFile')->willReturn($file);
        $file->method('getDefinitions')->willReturn($definitions);

        $array = array(
            'script' => array(':'),
            'services' => array('docker'),
        );
        $step = new Step($pipeline, 1, $array);

        $exec->expect('capture', 'docker', 0, 'container id by name');
        $exec->expect('capture', 'docker', 0, 'run container');
        $exec->expect('pass', '~^<<\'SCRIPT\' docker exec ~', 0, 'run step script');
        $exec->expect('capture', 'docker', 'kill');
        $exec->expect('capture', 'docker', 'rm');

        $this->expectOutputRegex('~\Qpipelines-integration-test-2.no-name.null.pipelines-test-suite\E~');
        $actual = $runner->runStep($step);
        $this->assertSame(0, $actual);
    }
}
