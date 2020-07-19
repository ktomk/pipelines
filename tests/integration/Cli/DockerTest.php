<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Integration\Cli;

use Ktomk\Pipelines\Cli\Docker;
use Ktomk\Pipelines\Cli\Exec;
use Ktomk\Pipelines\TestCase;

/**
 * Class ProcTest
 *
 * @coversNothing
 */
class DockerTest extends TestCase
{
    public function testCreation()
    {
        $exec = new Exec();
        $docker = new Docker($exec);
        self::assertInstanceOf('Ktomk\Pipelines\Cli\Docker', $docker);
    }

    public function testHasCommand()
    {
        $exec = new Exec();
        $docker = new Docker($exec);
        $actual = $docker->hasCommand();
        self::assertIsBool($actual);

        return $actual;
    }

    public function testVersion()
    {
        $exec = new Exec();
        $docker = new Docker($exec);

        $version = $docker->getVersion();
        if (null === $version) {
            self::assertNull($version);
        } else {
            self::assertIsString($version);
        }
    }
}
