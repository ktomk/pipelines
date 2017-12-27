<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Integration\Cli;

use Ktomk\Pipelines\Cli\App;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ktomk\Pipelines\Cli\App
 */
class AppTest extends TestCase
{
    function provideCommands()
    {
        return array(
            array('--help'),
            array('--show'),
            array('--images'),
            array('--list'),
            array('--dry-run'),
        );
    }

    /**
     * @param string $command
     * @dataProvider provideCommands
     */
    function testSuccessfulCommands($command)
    {
        $app = new App();
        $args = array_merge((array) 'pipelines-test', (array) $command);
        ob_start();
        $status = $app->main($args);
        ob_end_clean();
        $this->assertSame(0, $status);
    }
}
