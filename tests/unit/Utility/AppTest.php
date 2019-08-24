<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\TestCase;

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

    public function testMainExceptionHandlingArgsException()
    {
        $app = new App(new Streams(null, null, 'php://output'));
        $this->expectOutputRegex('{^pipelines: option --prefix requires an argument\n--------\nclass....:}');
        $actual = $app->main(array('cmd', '--debug', '--prefix'));
        $this->assertNotSame(0, $actual);
    }

    public function testMainExceptionHandlingParseException()
    {
        $app = new App(new Streams());
        $actual = $app->main(array(
            'cmd', '--file', 'tests/data/yml/invalid-pipeline.yml',
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
        $this->expectOutputRegex('{^pipelines: invalid prefix: \'123\'\n}');
        $actual = $app->main(array('cmd', '--prefix', '123'));
        $this->assertSame(1, $actual);
    }

    /**
     * --basename has invalid argument so exit status is 1
     */
    public function testInvalidEmptyBasename() {
        $app = new App(new Streams(null, null, 'php://output'));
        $this->expectOutputRegex('{^pipelines: not a basename: \'\'\n}');
        $actual = $app->main(array('cmd', '--basename', ''));
        $this->assertSame(1, $actual);
    }

    public function testEmptyFileStatus()
    {
        $app = new App(new Streams());
        $actual = $app->main(array('cmd', '--file', ''));
        $this->assertSame(1, $actual);
    }

    public function testFileNonDefaultBasenameChange()
    {
        $this->expectOutputRegex('{^info: --file overrides non-default --basename}');
        $app = new App(new Streams(null, 'php://output'));
        $actual = $app->main(array('cmd', '--basename', 'pipelines.yml', '--file', 'my-pipelines.yml', '-v'));
        $this->assertSame(1, $actual);
    }

    public function testFileAbsolutePath()
    {
        $file = __DIR__ . '/my-pipelines.yml';
        $this->expectOutputRegex(sprintf('{^pipelines: not a readable file: %s}', preg_quote($file, '{}')));
        $app = new App(new Streams(null, null, 'php://output'));
        $actual = $app->main(array('cmd', '--file', $file));
        $this->assertSame(1, $actual);
    }

    public function testBasenameLookupWorkingDirectoryChange()
    {
        $this->expectOutputRegex(sprintf(
            '{^info: changing working directory to %s$}m',
            preg_quote(dirname(dirname(dirname(__DIR__))), '{}')
        ));
        $app = new App(new Streams(null, 'php://output'));
        $actual = $app->main(array('cmd', '--working-dir', __DIR__, '-v', '--no-run'));
        $this->assertSame(0, $actual);
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
        $actual = $app->main(array('cmd', '--file', 'tests/data/yml/no-default-pipeline.yml'));
        $this->assertSame(1, $actual);
    }

    public function testShowPipelinesWithError()
    {
        $this->expectOutputRegex('{ERROR\s+\'image\' invalid Docker image}');
        $app = new App(new Streams(null, 'php://output'));
        $actual = $app->main(array('cmd', '--file', 'tests/data/yml/invalid-pipeline.yml', '--show'));
        $this->assertSame(1, $actual);
    }

    public function testUnknownDeployMode()
    {
        $this->expectOutputRegex('{^pipelines: unknown deploy mode \'flux-compensate\'}');
        $app = new App(new Streams(null, null, 'php://output'));
        $actual = $app->main(array('cmd', '--deploy', 'flux-compensate'));
        $this->assertSame(1, $actual);
    }

    public function testUnknownOption()
    {
        $option = '--meltdown-trampoline-kernel-patch';
        $this->expectOutputRegex(sprintf('{^pipelines: unknown option: %s}', preg_quote($option, '{}')));
        $app = new App(new Streams(null, null, 'php://output'));
        $actual = $app->main(array('cmd', $option));
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
            array(array('--keep', '--dry-run')),
            array(array('--no-keep', '--no-run')),
            array(array('--error-keep', '--dry-run')),
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

    public function provideErroneousArguments()
    {
        return array(
            array(
                "pipelines: --keep and --no-keep are exclusive\n",
                array('--keep', '--no-keep', '--no-run'),
            ),
            array(
                "pipelines: pipeline 'fromage/baguette' unavailable\n",
                array('--pipeline', 'fromage/baguette'),
            ),
        );
    }

    /**
     * @param string $output expected error output
     * @param array $arguments
     * @dataProvider provideErroneousArguments
     */
    public function testErroneousCommands($output, array $arguments)
    {
        $this->expectOutputString($output);
        $app = new App(new Streams(null, null, 'php://output'));
        $args = array_merge((array)'pipelines-test', $arguments);
        $status = $app->main($args);
        $this->assertSame(1, $status);
    }
}
