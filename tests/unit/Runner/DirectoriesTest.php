<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\LibFs;
use Ktomk\Pipelines\TestCase;

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

    public function testCreationWithMissingDirectory()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid project directory ');
        new Directories(array('HOME' => ''), '');
    }

    public function testCreationWithMissingHome()
    {
        $this->setExpectedException('InvalidArgumentException', 'Server must contain a "HOME" entry');
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

    public static function getTestProject()
    {
        return LibFs::normalizePathSegments(__DIR__ . '/../../..');
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
