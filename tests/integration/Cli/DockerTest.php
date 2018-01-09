<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Integration\Cli;

use Ktomk\Pipelines\Cli\Docker;
use Ktomk\Pipelines\Cli\Exec;
use PHPUnit\Framework\TestCase;

/**
 * Class ProcTest
 *
 * @covers \Ktomk\Pipelines\Cli\Docker
 */
class DockerTest extends TestCase
{
    function testCreation()
    {
        $exec = new Exec();
        $docker = new Docker($exec);
        $this->assertInstanceOf('Ktomk\Pipelines\Cli\Docker', $docker);
    }

    function testHasCommand()
    {
        $exec = new Exec();
        $docker = new Docker($exec);
        $actual = $docker->hasCommand();
        $this->assertInternalType('bool', $actual);

        return $actual;
    }

    /**
     * @depends testHasCommand
     */
    function testVersion($hasCommand)
    {
        if (!$hasCommand) {
            $this->markTestSkipped('no docker command in test system');
        }

        $exec = new Exec();
        $docker = new Docker($exec);

        $version = $docker->getVersion();
        $this->assertInternalType('string', $version);
    }
}
