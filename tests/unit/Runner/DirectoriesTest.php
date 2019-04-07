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
    public static function getTestProject()
    {
        return LibFs::normalizePathSegments(__DIR__ . '/../../..');
    }

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
        $this->setExpectedException('InvalidArgumentException', 'No $HOME in environment');
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
        $directories = new Directories($_SERVER, self::getTestProject());
        $this->assertSame(
            $_SERVER['HOME'] . '/.pipelines/' . basename(self::getTestProject()),
            $directories->getPipelineLocalDeploy()
        );
    }

    public function provideBaseDirectories()
    {
        $home = '/home/dulcinea';

        return array(
            array('XDG_CACHE_HOME', null, array('HOME' => $home), $home . '/.cache'),
            array('XDG_DATA_HOME', null, array('HOME' => $home), $home . '/.local/share'),
            array('XDG_DATA_HOME', null, array('XDG_DATA_HOME' => '/usr/share', 'HOME' => ''), '/usr/share'),
            array('XDG_DATA_HOME', 'pipelines/static-docker', array('HOME' => $home), $home . '/.local/share/pipelines/static-docker'),
        );
    }

    /**
     *
     * @dataProvider provideBaseDirectories
     *
     * @param $type
     * @param null|string $suffix
     * @param array $env
     * @param string $expected
     */
    public function testGetBaseDirectory($type, $suffix, array $env, $expected)
    {
        $directories = new Directories($env, self::getTestProject());

        $this->assertSame($expected, $directories->getBaseDirectory($type, $suffix));
    }

    public function testGetBaseDirectoryThrows()
    {
        $directories = new Directories(array('HOME' => ''), self::getTestProject());

        $this->setExpectedException('InvalidArgumentException', 'XDG_FOO42_HOME');
        $directories->getBaseDirectory('XDG_FOO42_HOME');
    }
}
