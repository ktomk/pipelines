<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Ktomk\Pipelines\Cli\App
 */
class AppTest extends TestCase
{
    function testCreation()
    {
        $app = App::create();
        $this->assertNotNull($app);
    }

    /**
     * @outputBuffering
     */
    public function testHelpOption()
    {
        $app = new App();

        $this->expectOutputRegex('~^usage: pipelines ~');
        $status = $app->main(array('cmd', '--help'));
        $this->assertSame(0, $status);
    }
}
