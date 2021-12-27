<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker\Provision;

use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\TestCase;

/**
 * @coversNothing
 */
class TarCopierTest extends TestCase
{
    /**
     * @covers \Ktomk\Pipelines\Runner\Docker\Provision\TarCopier::extMakeEmptyDirectory
     *
     * @return void
     */
    public function testExtMakeEmptyDirectoryWithRootPath()
    {
        $exec = new ExecTester($this);
        self::assertSame(0, TarCopier::extMakeEmptyDirectory($exec, '*test-run*', '', '/'));
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Docker\Provision\TarCopier::extMakeEmptyDirectory
     *
     * @return void
     */
    public function testExtMakeEmptyDirectoryWithEmptySourceAndNonRootPath()
    {
        $exec = new ExecTester($this);
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('empty source');
        TarCopier::extMakeEmptyDirectory($exec, '*test-run*', '', '/foo');
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Docker\Provision\TarCopier::extMakeEmptyDirectory
     *
     * @return void
     */
    public function testExtMakeEmptyDirectoryWithNonDirectorySourceAndNonRootPath()
    {
        $exec = new ExecTester($this);
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('not a directory: ');
        TarCopier::extMakeEmptyDirectory($exec, '*test-run*', __FILE__, '/foo');
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Docker\Provision\TarCopier::extMakeEmptyDirectory
     *
     * @return void
     */
    public function testExtMakeEmptyDirectoryWithNonRootPath()
    {
        $exec = new ExecTester($this);
        $exec->expect('pass', "~cd /tmp/pipelines-cp\\.[^/]+/\\. && tar c -h -f - --no-recursion \\./foo/bar | docker  cp - '\\*test-run\\*:/\\.'~");
        self::assertSame(0, TarCopier::extMakeEmptyDirectory($exec, '*test-run*', __DIR__, '/foo/bar'));
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Docker\Provision\TarCopier::extCopyDirectory
     *
     * @return void
     */
    public function testExtCopyDirectoryHappyPath()
    {
        $exec = new ExecTester($this);
        $exec->expect('pass', "cd foo/. && tar c -f - . | docker  cp - '*test-run*:bar'", 0);
        self::assertSame(0, TarCopier::extCopyDirectory($exec, '*test-run*', 'foo', 'bar'));
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Docker\Provision\TarCopier::extCopyDirectory
     *
     * @return void
     */
    public function testExtCopyDirectoryThrowsOnEmptySource()
    {
        $exec = new ExecTester($this);
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('empty source');
        TarCopier::extCopyDirectory($exec, '*test-run*', '', '');
    }

    /**
     * @covers \Ktomk\Pipelines\Runner\Docker\Provision\TarCopier::extDeployDirectory
     *
     * @return void
     */
    public function testExtDeployDirectoryQuickHappyPathDribble()
    {
        $exec = new ExecTester($this);
        $exec->expect('pass', "cd foo/. && tar c -f - . | docker  cp - '*test-run*:/'");
        self::assertSame(0, TarCopier::extDeployDirectory($exec, '*test-run*', 'foo', '/'));

        $exec->expect('pass', "~cd /tmp/pipelines-cp\\.[^/]+/\\. && tar c -h -f - --no-recursion \\./foo/bar | docker  cp - '\\*test-run\\*:/\\.'~");
        $exec->expect('pass', "~cd [^ ]+ && tar c -f - \\. | docker  cp -~");
        self::assertSame(0, TarCopier::extDeployDirectory($exec, '*test-run*', __DIR__, '/foo/bar'));

        $exec->expect('pass', "~cd /tmp/pipelines-cp\\.[^/]+/\\. && tar c -f - \\./failure | docker  cp - '\\*test-run\\*:/\\.'~", 42);
        self::assertSame(42, TarCopier::extDeployDirectory($exec, '*test-run*', __DIR__, '/failure'));
    }
}
