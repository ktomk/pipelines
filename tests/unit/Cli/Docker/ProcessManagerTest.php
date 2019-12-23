<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli\Docker;

use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\TestCase;

/**
 * Class DockerProcessManagerTest
 *
 * @covers \Ktomk\Pipelines\Cli\Docker\ProcessManager
 */
class ProcessManagerTest extends TestCase
{
    public function testCreation()
    {
        $ps = new ProcessManager(new Exec());
        $this->assertInstanceOf('Ktomk\Pipelines\Cli\Docker\ProcessManager', $ps);
    }

    public function testFindAllContainerIdsByNamePrefix()
    {
        $exec = new ExecTester($this);
        $exec->expect('capture', 'docker', 0);

        $ps = new ProcessManager($exec);
        $this->assertInternalType('array', $ps->findAllContainerIdsByNamePrefix('pipelines'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid container name prefix "**wrong**"
     */
    public function testInvalidPrefix()
    {
        $exec = new ExecTester($this);
        $ps = new ProcessManager($exec);
        $ps->findAllContainerIdsByNamePrefix('**wrong**');
    }

    public function testFindRunningContainerIdsByNamePrefix()
    {
        $exec = new ExecTester($this);
        $exec->expect('capture', 'docker', 0);

        $ps = new ProcessManager($exec);
        $this->assertInternalType('array', $ps->findRunningContainerIdsByNamePrefix('pipelines'));
    }

    public function testKill()
    {
        $exec = new ExecTester($this);
        $exec->expect('capture', 'docker', 0);

        $ps = new ProcessManager($exec);
        $this->assertInternalType('int', $ps->kill(
            'fc2ed48d903ddba765dd89a6f82baaed4612c0c23aa2558320aad889dd29d74c'
        ));
    }

    public function testRemove()
    {
        $exec = new ExecTester($this);
        $exec->expect('capture', 'docker', 0);

        $ps = new ProcessManager($exec);
        $this->assertInternalType('int', $ps->remove(
            array('fc2ed48d903ddba765dd89a6f82baaed4612c0c23aa2558320aad889dd29d74c')
        ));
    }

    public function testFindAllContainerIdsByName()
    {
        $exec = new ExecTester($this);
        $exec->expect('capture', 'docker', 0, 'docke ps');
        $pm = new ProcessManager($exec);
        $this->assertSame(
            array(),
            $pm->findAllContainerIdsByName('foo')
        );
    }

    public function testZapContainersByName()
    {
        $pm = $this->createPartialMock(
            'Ktomk\Pipelines\Cli\Docker\ProcessManager',
            array('findAllContainerIdsByName', 'kill', 'remove')
        );
        $pm->expects($this->once())
            ->method('kill');
        $pm->expects($this->once())
            ->method('remove');
        $pm->method('findAllContainerIdsByName')
            ->willReturn(array('1234567'));
        $pm->zapContainersByName('foo');
    }

    public function testZapContainersByNameNoMatches()
    {
        $pm = $this->createPartialMock(
            'Ktomk\Pipelines\Cli\Docker\ProcessManager',
            array('findAllContainerIdsByName')
        );
        $pm->zapContainersByName('foo');
        $this->addToAssertionCount(1);
    }
}
