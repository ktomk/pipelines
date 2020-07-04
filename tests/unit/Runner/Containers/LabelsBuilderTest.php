<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Containers;

use Ktomk\Pipelines\TestCase;

/**
 * Class LabelsBuilderTest
 *
 * @package Ktomk\Pipelines\Runner\Containers
 * @covers \Ktomk\Pipelines\Runner\Containers\LabelsBuilder
 */
class LabelsBuilderTest extends TestCase
{
    public function testCreation()
    {
        $builder = new LabelsBuilder();
        $this->assertInstanceOf('Ktomk\Pipelines\Runner\Containers\LabelsBuilder', $builder);
    }

    public function testCreationFromRunner()
    {
        $runner = $this->createConfiguredMock('Ktomk\Pipelines\Runner\Runner', array(
            'getPrefix' => 'prefix',
            'getProject' => 'project',
            'getProjectDirectory' => '/path/to/project',
        ));

        $builder = LabelsBuilder::createFromRunner($runner);
        $this->assertInstanceOf('Ktomk\Pipelines\Runner\Containers\LabelsBuilder', $builder);
    }

    public function testSetPrefix()
    {
        $builder = new LabelsBuilder();
        $this->assertSame($builder, $builder->setPrefix('prefix'));
    }

    public function testSetProject()
    {
        $builder = new LabelsBuilder();
        $this->assertSame($builder, $builder->setProject('project'));
    }

    public function testSetProjectDirectory()
    {
        $builder = new LabelsBuilder();
        $this->assertSame($builder, $builder->setProjectDirectory('/path/to/project'));
    }

    public function testSetRole()
    {
        $builder = new LabelsBuilder();
        $this->assertSame($builder, $builder->setRole('step'));
    }

    public function testToArrayWithRole()
    {
        $runner = $this->createConfiguredMock('Ktomk\Pipelines\Runner\Runner', array(
            'getProject' => 'project',
            'getProjectDirectory' => '/path/to/project',
            'getPrefix' => 'prefix',
        ));

        $builder = LabelsBuilder::createFromRunner($runner);
        $expected = array(
            'pipelines.prefix' => 'prefix',
            'pipelines.role' => 'step',
            'pipelines.project.name' => 'project',
            'pipelines.project.path' => '/path/to/project',
        );

        $actual = $builder->setRole('step')->toArray();
        $this->assertSame($expected, $actual);
    }
}
