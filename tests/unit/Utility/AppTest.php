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
    public function testCreation()
    {
        $app = App::create();
        $this->assertNotNull($app);
    }

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

        $this->expectOutputRegex("{^pipelines version (@\.@\.@|[a-f0-9]{7}|\d+\.\d+\.\d+)(-\d+-g[a-f0-9]{7})?\+?\n}");
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
        $this->expectOutputRegex('{^usage: pipelines }');
        $actual = $app->main(array('cmd', '--verbose', '--prefix'));
        $this->assertSame(1, $actual);
    }

    /**
     * --prefix has invalid argument so exit status is 1
     */
    public function testInvalidPrefix() {
        $app = new App(new Streams(null, null, 'php://output'));
        $this->expectOutputRegex('{^Invalid prefix: \'123\'\n}');
        $actual = $app->main(array('cmd', '--prefix', '123'));
        $this->assertSame(1, $actual);
    }

    /**
     * --basename has invalid argument so exit status is 1
     */
    public function testInvalidEmptyBasename() {
        $app = new App(new Streams(null, null, 'php://output'));
        $this->expectOutputRegex('{^Not a basename: \'\'\n}');
        $actual = $app->main(array('cmd', '--basename', ''));
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

    public function provideArguments()
    {
        return array(
            array(array('--version')),
            array(array('--help')),
            array(array('--show')),
            array(array('--images')),
            array(array('--list')),
            array(array('--dry-run')),
            array(array('--verbose', '--dry-run')),
            array(array('--keep', '--no-run')),
            array(array('--no-keep', '--no-run')),
            array(array('--docker-list', '--dry-run')),
            array(array('--verbatim', '--dry-run')),
        );
    }

    /**
     * @param array $arguments
     * @dataProvider provideArguments
     */
    public function testSuccessfulCommands(array $arguments)
    {
        $app = new App(new Streams());
        $args = array_merge((array)'pipelines-test', $arguments);
        $status = $app->main($args);
        $this->assertSame(0, $status);
    }

    public function testKeepAndNoKeepExclusivity()
    {
        $this->expectOutputString("--keep and --no-keep are exclusive\n");
        $app = new App(new Streams(null, null, 'php://output'));
        $args = array('pipelines-test', '--keep', '--no-keep', '--no-run');
        $status = $app->main($args);
        $this->assertSame(1, $status);
    }
}
