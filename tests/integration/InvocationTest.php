<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Integration;

use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class InvocationTest extends TestCase
{
    function provideCommands()
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
    function testSuccessfulCommands($command)
    {
        $this->assert("bin/pipelines $command");
    }

    private function assert($command)
    {
        exec($command . ' 2>&1', $output, $status);
        if ($status !== 0) {
            echo "\n", $command, "\n", implode("\n", $output), "\n";
        }
        $this->assertEquals(0, $status);
    }
}
