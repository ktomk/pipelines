<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\File\Pipeline;

use Ktomk\Pipelines\File\File;
use Ktomk\Pipelines\TestCase;

/**
 *
 * @covers \Ktomk\Pipelines\File\Pipeline\StepCondition
 */
class StepConditionTest extends TestCase
{
    /**
     * @covers \Ktomk\Pipelines\File\Pipeline\Step
     *
     * @return void
     */
    public function testCreation()
    {
        $pipeline = File::createFromFile(__DIR__ . '/../../../data/yml/condition.yml')->getById('default');
        self::assertNotNull($pipeline);
        $condition = $pipeline->getSteps()->offsetGet(0)->getCondition();
        self::assertInstanceOf(__NAMESPACE__ . '\StepCondition', $condition);
        self::assertSame(array('path1/*.xml', 'path2/**'), $condition->getIncludePaths());
    }

    public function testUsageExample()
    {
        $file = File::createFromFile(__DIR__ . '/../../../data/yml/condition-usage-example.yml');
        self::assertNotNull($file);
        $expected = 3;
        $actual = 0;
        foreach ($file->getPipelines()->getPipelines() as $id => $pipeline) {
            foreach ($pipeline->getSteps() as $index => $step) {
                $condition = $step->getCondition();
                self::assertNotNull($condition, "${id}: #${index}");
                self::assertNotEmpty($condition->getIncludePaths());
                $actual++;
            }
        }
        self::assertSame($expected, $actual, 'count of condition(s)');
    }

    public function provideCreationErrors()
    {
        return array(
            array(array()),
            array(array('condition')),
            array(array('changesets' => 1)),
            array(array('changesets' => array())),
            array(array('changesets' => array('includePaths' => null))),
            array(array('changesets' => array('includePaths' => array()))),
            array(array('changesets' => array('includePaths' => array(1)))),
            array(array('changesets' => array('includePaths' => array('')))),
            array(array('changesets' => array('includePaths' => array(2 => 1)))),
            array(array('changesets' => array('includePaths' => array('path', 1)))),
        );
    }

    /**
     * @dataProvider provideCreationErrors()
     *
     * @return void
     */
    public function testCreationErrors(array $definition)
    {
        $this->expectException('Ktomk\Pipelines\File\ParseException');
        new StepCondition($definition);
    }
}
