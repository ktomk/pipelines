<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use PHPUnit\Framework\TestCase;

/**
 * Class DirectoriesTest
 *
 * @covers \Ktomk\Pipelines\Runner\Directories
 */
class DirectoriesTest extends TestCase
{
    public function testCreation()
    {
        $project = realpath(__DIR__ . '/../../..');
        $directories = new Directories($_SERVER, $project);
        $this->assertInstanceOf(
            'Ktomk\Pipelines\Runner\Directories',
            $directories
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid project directory ""
     */
    public function testCreationWithMissingDirectory()
    {
        new Directories($_SERVER, '');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Server must contain a "HOME" entry
     */
    public function testCreationWithMissingHome()
    {
        new Directories(array(), __DIR__);
    }

    public function testName()
    {
        $project = realpath(__DIR__ . '/../..');
        $directories = new Directories($_SERVER, $project);
        $this->assertSame(
            'tests',
            $directories->getName()
        );
    }

    public function testProject()
    {
        $project = realpath(__DIR__ . '/../..');
        $directories = new Directories($_SERVER, $project);
        $this->assertSame(
            $project,
            $directories->getProject()
        );
    }

    public function testPipelineLocalDeploy()
    {
        $project = realpath(__DIR__ . '/../../..');
        $directories = new Directories($_SERVER, $project);
        $this->assertSame(
            $_SERVER['HOME'] . '/.pipelines/' . basename($project),
            $directories->getPipelineLocalDeploy()
        );
    }
}
