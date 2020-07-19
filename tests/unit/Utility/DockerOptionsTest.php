<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\Docker\ProcessManager;
use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\TestCase;

/**
 * Class DockerOptionsTest
 *
 * @covers \Ktomk\Pipelines\Utility\DockerOptions
 * @covers \Ktomk\Pipelines\Cli\ExecTester
 */
class DockerOptionsTest extends TestCase
{
    public function testBind()
    {
        $options = DockerOptions::bind(
            Args::create(array('cmd')),
            new Exec(),
            'prefix',
            new Streams()
        );
        self::assertInstanceOf(
            'Ktomk\Pipelines\Utility\DockerOptions',
            $options
        );
    }

    /**
     * @throws StatusException
     */
    public function testCreation()
    {
        $exec = ExecTester::create($this);

        $options = new DockerOptions(
            Args::create(array('cmd')),
            $exec,
            'prefix',
            new Streams(),
            new ProcessManager($exec)
        );

        $options->run();
        $this->addToAssertionCount(1);
    }

    /**
     * @throws StatusException
     */
    public function testHappyPath()
    {
        $this->expectException('Ktomk\Pipelines\Utility\StatusException');
        $this->expectExceptionMessage('');
        $this->expectExceptionCode(0);

        $exec = ExecTester::create($this);

        $options = new DockerOptions(
            Args::create(array('cmd', '--docker-list', '--docker-kill', '--docker-clean')),
            $exec,
            'prefix',
            new Streams(),
            new ProcessManager($exec)
        );

        $exec
            ->expect('pass', 'docker ps -a', 0)
            ->expect('capture', 'docker', "123\n456")
            ->expect('capture', 'docker', '456')
            ->expect('capture', 'docker', 0)
            ->expect('capture', 'docker', 0)
            ;

        $options->run();
    }

    /**
     * @throws StatusException
     */
    public function testNoContainersToKill()
    {
        $this->expectException('Ktomk\Pipelines\Utility\StatusException');
        $this->expectExceptionMessage('');
        $this->expectExceptionCode(0);

        $exec = ExecTester::create($this);

        $options = new DockerOptions(
            Args::create(array('cmd', '--docker-list', '--docker-kill', '--docker-clean')),
            $exec,
            'prefix',
            new Streams(),
            new ProcessManager($exec)
        );

        $exec
            ->expect('pass', 'docker ps -a', 0)
            ->expect('capture', 'docker', '')
            ->expect('capture', 'docker', '')
        ;

        $options->run();
    }

    /**
     * @throws StatusException
     */
    public function testDockerZap()
    {
        $this->expectException('Ktomk\Pipelines\Utility\StatusException');
        $this->expectExceptionMessage('');
        $this->expectExceptionCode(0);

        $exec = ExecTester::create($this);

        $options = new DockerOptions(
            Args::create(array('cmd', '--docker-zap')),
            $exec,
            'abc',
            new Streams(),
            new ProcessManager($exec)
        );

        $exec
            ->expect('capture', 'docker', '')
            ->expect('capture', 'docker', '')
        ;

        $options->run();
    }
}
