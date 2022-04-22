<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\TestCase;
use Ktomk\Pipelines\Yaml\Yaml;

/**
 * @covers \Ktomk\Pipelines\Utility\App
 */
class AppTest extends TestCase
{
    public function testCreation()
    {
        $app = App::create();
        self::assertNotNull($app);
    }

    public function testMainExceptionHandlingArgsException()
    {
        $app = new App(new Streams(null, null, 'php://output'));
        $this->expectOutputRegex(
            '{^pipelines: option --prefix requires an argument\npipelines: version .*\n--------\nclass....:}'
        );
        $actual = $app->main(array('cmd', '--debug', '--prefix'));
        self::assertNotSame(0, $actual);
    }

    public function testMainExceptionHandlingParseException()
    {
        $app = new App(new Streams());
        $actual = $app->main(array(
            'cmd', '--file', 'test/data/yml/invalid/pipeline.yml',
            '--pipeline', 'custom/unit-tests',
        ));
        self::assertSame(2, $actual);
    }

    /**
     * --verbose as old test behaviour,
     * --prefix misses argument so exit status is 1 and usage information is shown
     */
    public function testMainVerbosePrinter()
    {
        $app = new App(new Streams(null, 'php://output', null));
        $this->expectOutputRegex('{^usage: pipelines }m');
        $actual = $app->main(array('cmd', '--verbose', '--prefix'));
        self::assertSame(1, $actual);
    }

    /**
     * --prefix has invalid argument so exit status is 1
     */
    public function testInvalidPrefix()
    {
        $app = new App(new Streams(null, null, 'php://output'));
        $this->expectOutputRegex('{^pipelines: invalid prefix: \'123\'\n}');
        $actual = $app->main(array('cmd', '--prefix', '123'));
        self::assertSame(1, $actual);
    }

    /**
     * --basename has invalid argument so exit status is 1
     */
    public function testInvalidEmptyBasename()
    {
        $app = new App(new Streams(null, null, 'php://output'));
        $this->expectOutputRegex('{^pipelines: not a basename: \'\'\n}');
        $actual = $app->main(array('cmd', '--basename', ''));
        self::assertSame(1, $actual);
    }

    public function testEmptyFileStatus()
    {
        $app = new App(new Streams());
        $actual = $app->main(array('cmd', '--file', ''));
        self::assertSame(1, $actual);
    }

    public function testFileNonDefaultBasenameChange()
    {
        $this->expectOutputRegex('{^info: --file overrides non-default --basename$}m');
        $app = new App(new Streams(null, 'php://output'));
        $actual = $app->main(array('cmd', '--basename', 'pipelines.yml', '--file', 'my-pipelines.yml', '-v'));
        self::assertSame(1, $actual);
    }

    public function testFileAbsolutePath()
    {
        $file = __DIR__ . '/my-pipelines.yml';
        $this->expectOutputRegex(sprintf('{^pipelines: not a readable file: %s}', preg_quote($file, '{}')));
        $app = new App(new Streams(null, null, 'php://output'));
        $actual = $app->main(array('cmd', '--file', $file));
        self::assertSame(1, $actual);
    }

    /**
     * passing '-' as file for stdin is fine, but parsing won't work as it waits for input
     */
    public function testFileIsStdinSpecialFile()
    {
        Yaml::$classes = array('');

        $this->expectOutputRegex(
            sprintf('{^info: reading pipelines from stdin\npipelines: fatal: No YAML parser available$}m')
        );
        $app = new App(new Streams(null, 'php://output', 'php://output'));
        $actual = $app->main(array('cmd', '--verbose', '--file', '-'));
        self::assertSame(2, $actual);

        Yaml::$classes = array();
    }

    public function testBasenameLookupWorkingDirectoryChange()
    {
        $this->expectOutputRegex(sprintf(
            '{^info: changing working directory to %s$}m',
            preg_quote(dirname(dirname(dirname(__DIR__))), '{}')
        ));
        $app = new App(new Streams(null, 'php://output'));
        $actual = $app->main(array('cmd', '--working-dir', __DIR__, '-v', '--no-run'));
        self::assertSame(0, $actual);
    }

    public function testInvalidWorkingDirStatus()
    {
        $app = new App(new Streams());
        $actual = @$app->main(array('cmd', '--working-dir', '/foo/comes/bar/and/thanks/for/the-fish'));
        self::assertSame(2, $actual);
    }

    public function testNoPipelineToRunStatus()
    {
        $app = new App(new Streams());
        $actual = $app->main(array('cmd', '--file', 'test/data/yml/no-default-pipeline.yml'));
        self::assertSame(1, $actual);
    }

    public function testShowPipelinesWithError()
    {
        $this->expectOutputRegex('{ERROR\s+\'image\' invalid Docker image}');
        $app = new App(new Streams(null, 'php://output'));
        $actual = $app->main(array('cmd', '--file', 'test/data/yml/invalid/pipeline.yml', '--show'));
        self::assertSame(1, $actual);
    }

    public function testUnknownDeployMode()
    {
        $this->expectOutputRegex('{^pipelines: unknown deploy mode \'flux-compensate\'}');
        $app = new App(new Streams(null, null, 'php://output'));
        $actual = $app->main(array('cmd', '--deploy', 'flux-compensate'));
        self::assertSame(1, $actual);
    }

    public function testUnknownOption()
    {
        $option = '--meltdown-trampoline-kernel-patch';
        $this->expectOutputRegex(sprintf('{^pipelines: unknown option: %s}', preg_quote($option, '{}')));
        $app = new App(new Streams(null, null, 'php://output'));
        $actual = $app->main(array('cmd', $option));
        self::assertSame(1, $actual);
    }

    public function provideArguments()
    {
        return array(
            array(array('--version')),
            array(array('--help')),
            array(array('--show')),
            array(array('--show-pipelines')),
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
        self::assertSame(0, $status);
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
        self::assertSame(1, $status);
    }
}
