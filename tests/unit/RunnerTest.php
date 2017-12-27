<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\Cli\Exec;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ktomk\Pipelines\Runner
 */
class RunnerTest extends UnitTestCase
{
    function testRunning()
    {
        /** @var MockObject|Exec $exec */
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');
        $exec->method('pass')->willReturn(0);
        $exec->method('capture')->willReturn(0);

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, array(
                'image' => 'foo/bar:latest',
                'script' => array(':'),
            ))
        ));

        $runner = new Runner('pipelines-unit-test', '/tmp', $exec);

        $this->setOutputCallback(function() {});
        $actual = $runner->run($pipeline);
        $this->assertSame(0, $actual);
    }

    function testFailOnContainerCreation()
    {
        /** @var MockObject|Exec $exec */
        $exec = $this->createMock('Ktomk\Pipelines\Cli\Exec');
        $exec->expects($this->exactly(1))
            ->method('capture')->willReturnCallback(function($cmd, $args, &$out, &$err) {
                return 126;
            });

        /** @var MockObject|Pipeline $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');

        $pipeline->method('getSteps')->willReturn(array(
            new Step($pipeline, array(
                'image' => 'foo/bar:latest',
                'script' => array(':'),
            ))
        ));

        $runner = new Runner('pipelines-unit-test', '/tmp', $exec);

        $this->setOutputCallback(function() {});
        $actual = $runner->run($pipeline);
        $this->assertNotSame(0, $actual);
    }

    function testErrorStatusWithPipelineHavingEmptySteps()
    {
        /** @var Pipeline|MockObject $pipeline */
        $pipeline = $this->createMock('Ktomk\Pipelines\Pipeline');
        $pipeline->method('getSteps')->willReturn(array());

        $exec = new Exec();
        $exec->setActive(false);
        $runner = new Runner('pipelines-unit-test', '/tmp', $exec);
        $status = $runner->run($pipeline);
        $this->assertEquals(255, $status);
    }
}
