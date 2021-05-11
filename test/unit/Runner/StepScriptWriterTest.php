<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\TestCase;

/**
 * Class StepScriptWriterTest
 *
 * @covers \Ktomk\Pipelines\Runner\StepScriptWriter
 */
class StepScriptWriterTest extends TestCase
{
    public function testGetStepScript()
    {
        $step = $this->createMock('Ktomk\Pipelines\File\Pipeline\Step');
        $step->method('getScript')->willReturn(array('printf "hello world\n"'));
        $buffer = StepScriptWriter::writeStepScript($step->getScript());
        self::assertMatchesRegularExpression('~# this /bin/sh .+printf "hello world\\\\n".+~s', $buffer);
    }

    public function testGetAfterScript()
    {
        $buffer = StepScriptWriter::writeAfterScript(array());
        self::assertMatchesRegularExpression('~# this /bin/sh .+after-script.+~s', $buffer);
    }

    public function testGenerateCommandWithPipe()
    {
        $pipe = array(
            'pipe' => 'pipelines/pipe',
            'variables' => array(
                'HOME' => '/dev/null/outer/space',
            ),
        );
        $buffer = StepScriptWriter::generateCommand($pipe);
        self::assertMatchesRegularExpression('~# pipe feature is pending.+HOME.*~s', $buffer);
    }

    public function testGetAfterCommand()
    {
        self::assertNull(
            $nonStrict = StepScriptWriter::getLinePostCommand(false)
        );

        self::assertNotSame(
            $nonStrict,
            StepScriptWriter::getLinePostCommand(true)
        );
    }
}
