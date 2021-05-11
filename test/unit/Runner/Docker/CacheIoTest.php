<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker;

use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\Runner\RunOpts;
use Ktomk\Pipelines\TestCase;

/**
 * Class CacheHandlerTest
 *
 * @package Ktomk\Pipelines\Runner\Docker
 * @covers \Ktomk\Pipelines\Runner\Docker\CacheIo
 */
class CacheIoTest extends TestCase
{
    /**
     * @var null|ExecTester
     */
    private $execTester;

    public function testCreation()
    {
        $io = $this->newCacheIo('', 'container-id');
        self::assertInstanceOf('Ktomk\Pipelines\Runner\Docker\CacheIo', $io);
    }

    public function testCreationThrowsOnNonAbsoluteNonEmptyCachesDirectoryPath()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Caches directory: Not an absolute path: non-empty-and-relative');

        $this->newCacheIo('non-empty-and-relative', 'container-id');
    }

    /**
     * @return void
     */
    public function testStaticRunnerCachesDirectory()
    {
        $runner = $this->createTestRunnerMock();

        $actual = CacheIo::runnerCachesDirectory($runner);
        self::assertSame('/.cache//caches/test-project', $actual);
        #                         ^       ^
        #                  $HOME is here  `-- "pipelines" utility name is here
    }

    public function testStaticCreation()
    {
        $runner = $this->createTestRunnerMock();

        $io = CacheIo::createByRunner($runner, 'foo');
        self::assertInstanceOf('Ktomk\Pipelines\Runner\Docker\CacheIo', $io);
    }

    public function testCaptureStepCachesNonCapturing()
    {
        $io = $this->createPartialMock('Ktomk\Pipelines\Runner\Docker\CacheIo', array());
        $io->captureStepCaches(
            $this->createMock('Ktomk\Pipelines\File\Pipeline\Step'),
            false
        );
        $this->addToAssertionCount(1);
    }

    public function testMapCachePathContainerResolution()
    {
        $exec = $this->execTester();
        $exec->expect('capture', '~^docker ~', '/foo/bar', 'exec echo for resolve');

        $io = $this->newCacheIo('', '');

        self::assertSame('/foo/bar', $io->mapCachePath('relative'));
    }

    public function testMapCachePathRelative()
    {
        $exec = $this->execTester();
        $exec->expect('capture', '~^docker ~', 0, 'exec echo for resolve');

        $io = $this->newCacheIo('', '');

        self::assertSame('/relative', $io->mapCachePath('./relative'));
    }

    public function testDeployAndCaptureWithNoCacheFlag()
    {
        $io = $this->newCacheIo('', '', true);
        $step = $this->createMock('Ktomk\Pipelines\File\Pipeline\Step');
        $io->deployStepCaches($step);
        $this->addToAssertionCount(1);
        $io->captureStepCaches($step, true);
        $this->addToAssertionCount(1);
    }

    public function testDeployAndCaptureWithEmptyCacheDirectory()
    {
        $io = $this->newCacheIo('', '');
        $step = $this->createMock('Ktomk\Pipelines\File\Pipeline\Step');
        $io->deployStepCaches($step);
        $this->addToAssertionCount(1);
        $io->captureStepCaches($step, true);
        $this->addToAssertionCount(1);
    }

    public function testDeployAndCaptureWithEmptyCaches()
    {
        $io = $this->newCacheIo('/fake', '');
        $step = $this->createMock('Ktomk\Pipelines\File\Pipeline\Step');
        $step->method('getCaches')->willReturn(new \ArrayObject());
        $io->deployStepCaches($step);
        $this->addToAssertionCount(1);
        $io->captureStepCaches($step, true);
        $this->addToAssertionCount(1);
    }

    /**
     * @return ExecTester
     */
    private function execTester()
    {
        return $this->execTester = new ExecTester($this);
    }

    /**
     * Curry some constructor arguments
     *
     * @param string $caches
     * @param string $id
     * @param bool $noCache
     *
     * @return CacheIo
     */
    private function newCacheIo($caches, $id, $noCache = false)
    {
        return new CacheIo(
            $caches,
            $id,
            null,
            $this->createMock('Ktomk\Pipelines\Cli\Streams'),
            null === $this->execTester
                ? $this->createMock('Ktomk\Pipelines\Cli\Exec')
                : $this->execTester,
            $noCache
        );
    }

    /**
     * @return \Ktomk\Pipelines\Runner\Runner|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createTestRunnerMock()
    {
        $runner = $this->createMock('Ktomk\Pipelines\Runner\Runner');
        $runner->method('getDirectories')->willReturn(
            $this->createPartialMock('Ktomk\Pipelines\Runner\Directories', array())
        );
        $runner->method('getProject')->willReturn('test-project');
        $runner->method('getRunOpts')->willReturn(RunOpts::create());

        $runner->method('getStreams')->willReturn(
            $this->createMock('Ktomk\Pipelines\Cli\Streams')
        );

        $exec = null === $this->execTester
            ? $this->createMock('Ktomk\Pipelines\Cli\Exec')
            : $this->execTester;

        $runner->method('getExec')->willReturn($exec);

        $runner->method('getFlags')->willReturn(
            $this->createMock('Ktomk\Pipelines\Runner\Flags')
        );

        return $runner;
    }
}
