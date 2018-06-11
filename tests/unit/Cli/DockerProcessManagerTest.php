<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use PHPUnit\Framework\TestCase;

/**
 * Class DockerProcessManagerTest
 *
 * @covers \Ktomk\Pipelines\Cli\DockerProcessManager
 */
class DockerProcessManagerTest extends TestCase
{
    public function testCreation()
    {
        $ps = new DockerProcessManager();
        $this->assertInstanceOf('Ktomk\Pipelines\Cli\DockerProcessManager', $ps);
    }

    public function testFindAllContainerIdsByNamePrefix()
    {
        $exec = new ExecTester($this);
        $exec->expect('capture', 'docker', 0);

        $ps = new DockerProcessManager($exec);
        $this->assertInternalType('array', $ps->findAllContainerIdsByNamePrefix('pipelines'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid container name prefix "**wrong**"
     */
    public function testInvalidPrefix()
    {
        $exec = new ExecTester($this);
        $ps = new DockerProcessManager($exec);
        $ps->findAllContainerIdsByNamePrefix('**wrong**');
    }

    public function testFindRunningContainerIdsByNamePrefix()
    {
        $exec = new ExecTester($this);
        $exec->expect('capture', 'docker', 0);

        $ps = new DockerProcessManager($exec);
        $this->assertInternalType('array', $ps->findRunningContainerIdsByNamePrefix('pipelines'));
    }

    public function testKill()
    {
        $exec = new ExecTester($this);
        $exec->expect('capture', 'docker', 0);

        $ps = new DockerProcessManager($exec);
        $this->assertInternalType('int', $ps->kill(
            'fc2ed48d903ddba765dd89a6f82baaed4612c0c23aa2558320aad889dd29d74c'
        ));
    }

    public function testRemove()
    {
        $exec = new ExecTester($this);
        $exec->expect('capture', 'docker', 0);

        $ps = new DockerProcessManager($exec);
        $this->assertInternalType('int', $ps->remove(
            array('fc2ed48d903ddba765dd89a6f82baaed4612c0c23aa2558320aad889dd29d74c')
        ));
    }
}
