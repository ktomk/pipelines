<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\LibFsPath;
use Ktomk\Pipelines\Project;
use Ktomk\Pipelines\TestCase;

/**
 * Class DirectoriesTest
 *
 * @covers \Ktomk\Pipelines\Runner\Directories
 */
class DirectoriesTest extends TestCase
{
    /**
     * @return Project project directory path (this project) as test project
     */
    public static function getTestProject()
    {
        return new Project(LibFsPath::normalizeSegments(__DIR__ . '/../../..'));
    }

    public function testCreation()
    {
        $project = new Project(realpath(__DIR__ . '/../../..'));
        $directories = new Directories($_SERVER, $project);
        self::assertInstanceOf(
            'Ktomk\Pipelines\Runner\Directories',
            $directories
        );
    }

    public function testCreationWithMissingHome()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('$HOME unset or empty');
        new Directories(array('HOME' => ''), new Project(__DIR__));
    }

    public function testCreationWithNonPortableUtilityName()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Not a portable utility name: "-f"');
        new Directories(array('HOME' => '/greta-garbo'), new Project(__DIR__), '-f');
    }

    public function testName()
    {
        $project = realpath(__DIR__ . '/../..');
        $directories = new Directories($_SERVER, new Project($project));
        self::assertSame(
            'test',
            $directories->getName()
        );
    }

    /**
     * The project directory is that central, it is even now named
     * "pwd" which is "cwd", this is where the project lies.
     */
    public function testProjectWorkingDirectory()
    {
        $project = LibFsPath::normalizeSegments(__DIR__ . '/../..');
        $directories = new Directories($_SERVER, new Project($project));
        self::assertSame(
            $project,
            $directories->getProjectDirectory()
        );
    }

    public function testPipelineLocalDeploy()
    {
        $directories = new Directories($_SERVER, self::getTestProject());
        self::assertSame(
            $_SERVER['HOME'] . '/.pipelines/' . basename(self::getTestProject()),
            $directories->getPipelineLocalDeploy()
        );
    }

    public function provideBaseDirectories()
    {
        $home = '/home/dulcinea';

        return array(
            array('XDG_CACHE_HOME', null, array('HOME' => $home), $home . '/.cache/pipelines'),
            array('XDG_DATA_HOME', null, array('HOME' => $home), $home . '/.local/share/pipelines'),
            array('XDG_DATA_HOME', null, array('XDG_DATA_HOME' => '/usr/share', 'HOME' => $home), '/usr/share/pipelines'),
            array('XDG_DATA_HOME', 'static-docker', array('HOME' => $home), $home . '/.local/share/pipelines/static-docker'),
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

        self::assertSame($expected, $directories->getBaseDirectory($type, $suffix));
    }

    public function testGetBaseDirectoryThrows()
    {
        $directories = new Directories(array('HOME' => '/greta-garbo'), self::getTestProject());

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('XDG_FOO42_HOME');
        $directories->getBaseDirectory('XDG_FOO42_HOME');
    }
}
