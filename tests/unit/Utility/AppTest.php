<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Streams;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ktomk\Pipelines\Utility\App
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
        $app = new App(new Streams(null, 'php://output'));

        $this->expectOutputRegex('~^usage: pipelines ~');
        $status = $app->main(array('cmd', '--help'));
        $this->assertSame(0, $status);
    }

    public function testShowVersion()
    {
        $app = new App(new Streams(null, 'php://output'));

        $this->expectOutputString("pipelines version @.@.@\n");
        $app->main(array('cmd', '--version'));
    }

    public function testMainExceptionHandlingArgsException()
    {
        $app = new App(new Streams(null, null, 'php://output'));
        $this->expectOutputRegex('{^error: option \'prefix\' requires an argument\n--------\nclass....:}');
        $actual = $app->main(array('cmd', '--debug', '--prefix'));
        $this->assertNotSame(0, $actual);
    }

    public function testMainExceptionHandlingParseException()
    {
        $app = new App(new Streams());
        $actual = $app->main(array(
            'cmd', '--file', 'tests/data/invalid-pipeline.yml',
            '--pipeline', 'custom/unit-tests',
        ));
        $this->assertSame(2, $actual);
    }

    /**
     * --verbose gives version,
     * --prefix misses argument so exit status is 1
     */
    public function testMainVerbosePrinter()
    {
        $app = new App(new Streams(null, 'php://output', null));
        $this->expectOutputRegex('{^pipelines version @\.@\.@\n}');
        $actual = $app->main(array('cmd', '--verbose', '--prefix'));
        $this->assertSame(1, $actual);
    }

    public function testEmptyFileStatus()
    {
        $app = new App(new Streams());
        $actual = $app->main(array('cmd', '--file', ''));
        $this->assertSame(1, $actual);
    }

    public function testInvalidWorkingDirStatus()
    {
        $app = new App(new Streams());
        $actual = @$app->main(array('cmd', '--working-dir', '/foo/comes/bar/and/thanks/for/the-fish'));
        $this->assertSame(2, $actual);
    }

    public function testNoPipelineToRunStatus()
    {
        $app = new App(new Streams());
        $actual = $app->main(array('cmd', '--file', 'tests/data/no-default-pipeline.yml'));
        $this->assertSame(1, $actual);
    }

    public function testShowPipelinesWithError()
    {
        $this->expectOutputRegex('{ERROR\s+\'image\' invalid Docker image}');
        $app = new App(new Streams(null, 'php://output'));
        $actual = $app->main(array('cmd', '--file', 'tests/data/invalid-pipeline.yml', '--show'));
        $this->assertSame(1, $actual);
    }

    public function testUnknownDeployMode()
    {
        $this->expectOutputRegex('{^Unknown deploy mode \'flux-compensate\'}');
        $app = new App(new Streams(null, null, 'php://output'));
        $actual = $app->main(array('cmd', '--deploy', 'flux-compensate'));
        $this->assertSame(1, $actual);
    }
}
