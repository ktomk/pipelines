<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Integration\File;

use Ktomk\Pipelines\TestCase;

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
        $this->assert("bin/pipelines --file test/data/yml/images.yml ${command}");
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
