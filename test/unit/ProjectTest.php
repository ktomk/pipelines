<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\Utility\App;

/**
 * Class ProjectTest
 *
 * @package Ktomk\Pipelines
 * @covers \Ktomk\Pipelines\Project
 */
class ProjectTest extends TestCase
{
    /**
     * @return Project
     */
    public function testCreation()
    {
        $project = new Project('test-project-path');
        self::assertInstanceOf('Ktomk\Pipelines\Project', $project);

        return $project;
    }

    public function testRequirePath()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid project directory: ""');
        new Project('');
    }

    public function testGetName()
    {
        $project = new Project(__DIR__ . '/test-project');
        self::assertSame('test-project', $project->getName());
    }

    public function testToString()
    {
        $project = new Project(__DIR__ . '/test-project');
        self::assertSame('test-project', (string)$project, 'project string represented by name');
    }

    public function testGetDirectory()
    {
        $path = __DIR__;
        $project = new Project($path);
        self::assertSame($path, $project->getPath());
    }

    /**
     * @depends testCreation
     *
     * @param Project $project
     *
     * @return void
     */
    public function testGetAndSetPrefix(Project $project)
    {
        self::assertNull($project->getPrefix());
        $project->setPrefix(App::UTILITY_NAME);
        self::assertSame(App::UTILITY_NAME, $project->getPrefix());

        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage('invalid prefix: "99f"');
        $project->setPrefix('99f');
    }
}
