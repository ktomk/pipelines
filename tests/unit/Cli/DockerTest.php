<?php

/* this file is part of pipelines */

/** @noinspection ALL */

namespace Ktomk\Pipelines\Cli;

use Ktomk\Pipelines\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ktomk\Pipelines\Cli\Docker
 */
class DockerTest extends TestCase
{
    public function testCommandDetectionAndVersionPaths()
    {
        $procFail = $this->createMock('Ktomk\Pipelines\Cli\Proc');
        $procGood = $this->createMock('Ktomk\Pipelines\Cli\Proc');
        $procGood->method('getStatus')->willReturn(0);
        $procGood->method('getStandardOutput')->willReturnCallback(
            function () use (&$procGoodOutput) {
                return $procGoodOutput;
            }
        );

        /** @var Exec|MockObject $exec */
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');
        $results = array(
            $procFail,
            $procFail,
            $procFail,
            $procGood,
            $procGood,
            $procGood,
            $procGood,
        );
        $exec->method('capture')->willReturnCallback(
            function ($command, $args, &$out, &$err = null) use ($results) {
                static $call = -1;
                $call++;
                /** @var Proc $proc */
                $proc = $results[$call];
                $out = $proc->getStandardOutput();
                $err = $proc->getStandardError();

                return $proc->getStatus();
            }
        );

        $docker = new Docker($exec);
        self::assertFalse($docker->hasCommand());
        self::assertNull($docker->getVersion());

        self::assertNull($docker->getVersion());
        self::assertSame('0.0.0-err', @$docker->getVersion());

        $procGoodOutput = "17.09.1-ce\n";
        self::assertSame('17.09.1-ce', $docker->getVersion());
        unset($procGoodOutput);
    }

    public function testHostDeviceMount()
    {
        /** @var Exec|MockObject $exec */
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');
        $exec->method('capture')->willReturnCallback(function ($cmd, $args, &$stdout) {
            $stdout = file_get_contents(__DIR__ . '/../../data/docker-inspect.json');

            return 0;
        });

        $docker = new Docker($exec);
        $actual = $docker->hostDevice('container-name', '/app');
        self::assertSame('/home/user/workspace/projects/pipelines', $actual, 'extraction from json fixture');
    }

    public function testHostDeviceMountOnNonMountPoint()
    {
        /** @var Exec|MockObject $exec */
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');
        $exec->method('capture')->willReturnCallback(function ($cmd, $args, &$stdout) {
            $stdout = file_get_contents(__DIR__ . '/../../data/docker-inspect.json');

            return 0;
        });

        $docker = new Docker($exec);
        $actual = $docker->hostDevice('container-name', '/thanks-for-the-fish');
        self::assertSame('/thanks-for-the-fish', $actual, 'fall back on non-mount-point');
    }

    public function testHostDeviceMountDockerInspectFails()
    {
        /** @var Exec|MockObject $exec */
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');
        $exec->method('capture')->willReturn(1);

        $docker = new Docker($exec);
        $actual = $docker->hostDevice('container-name', '/app');
        self::assertSame('/app', $actual, 'docker command fails');
    }

    public function testHostDeviceMountJsonParseFailure()
    {
        /** @var Exec|MockObject $exec */
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');
        $exec->method('capture')->willReturnCallback(function ($cmd, $args, &$stdout) {
            $stdout = 'Error: file not found or whatever';

            return 0;
        });

        $docker = new Docker($exec);
        $actual = $docker->hostDevice('container-name', '/app');
        self::assertSame('/app', $actual, 'extraction from json fixture');
    }

    public function testGetProcessManager()
    {
        self::assertInstanceOf(
            'Ktomk\Pipelines\Cli\Docker\ProcessManager',
            Docker::create()->getProcessManager()
        );
    }
}
