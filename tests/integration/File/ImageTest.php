<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Integration\File;

use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class ImageTest extends TestCase
{
    public function provideCommands()
    {
        return array(
            array('--show'),
            array('--images'),
            array('--list'),
        );
    }

    /**
     * @param string $command
     * @dataProvider provideCommands
     */
    public function testSuccessfulCommands($command)
    {
        $this->assert("bin/pipelines --file tests/data/images.yml $command");
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
