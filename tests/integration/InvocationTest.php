<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Integration;

use Ktomk\Pipelines\TestCase;

/**
 * @coversNothing
 */
class InvocationTest extends TestCase
{
    public function provideCommands()
    {
        return array(
            array('--help'),
            array('--show'),
            array('--images'),
            array('--list'),
            'prefer list over pipeline parameter' => array('--pipeline --list'),
            array('--dry-run'),
        );
    }

    /**
     * @param string $command
     * @dataProvider provideCommands
     */
    public function testSuccessfulCommands($command)
    {
        $this->assert("bin/pipelines ${command}");
    }

    private function assert($command)
    {
        exec($command . ' 2>&1', $output, $status);
        if (0 !== $status) {
            echo "\n", $command, "\n", implode("\n", $output), "\n";
        }
        self::assertSame(0, $status);
    }
}
