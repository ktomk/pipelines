<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Integration\Utility;

use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\TestCase;
use Ktomk\Pipelines\Utility\App;

/**
 * @coversNothing
 */
class AppTest extends TestCase
{
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

    public function testInvalidPrefixGivesError()
    {
        $app = new App(new Streams(null, null, 'php://output'));
        $this->expectOutputString("pipelines: invalid prefix: '!\$\"'\n");
        $args = array(
            'pipelines-test',
            '--prefix',
            '!$"',
        );
        $status = $app->main($args);
        self::assertSame(1, $status);
    }

    public function testEmptyBasenameGivesError()
    {
        $app = new App(new Streams(null, null, 'php://output'));
        $this->expectOutputString("pipelines: not a basename: ''\n");
        $args = array(
            'pipelines-test',
            '--basename',
            '',
        );
        $status = $app->main($args);
        self::assertSame(1, $status);
    }

    public function testFileOverridesBasenameVerbose()
    {
        $app = new App(new Streams(null, 'php://output'));
        $this->expectOutputRegex(
            '{^info: --file overrides non-default --basename$}m'
        );
        $args = array(
            'pipelines-test',
            '--verbose',
            '--file',
            'super.yml',
            '--basename',
            'my.yml',
            '--no-run',
        );
        $status = $app->main($args);
        self::assertSame(1, $status);
    }

    public function testNonReadableFilename()
    {
        $app = new App(new Streams(null, null, 'php://output'));
        $this->expectOutputString(
            'pipelines: not a readable file: '
            . "/rooter/home/galore/not/found/super.yml\n"
        );
        $args = array(
            'pipelines-test',
            '--file',
            '/rooter/home/galore/not/found/super.yml',
            '--no-run',
        );
        $status = $app->main($args);
        self::assertSame(1, $status);
    }

    public function testUnknownOption()
    {
        $app = new App(new Streams(null, null, 'php://output'));
        $this->expectOutputString(
            "pipelines: unknown option: --for-the-fish-thank-you\n"
        );
        $args = array(
            'pipelines-test',
            '--for-the-fish-thank-you',
        );
        $status = $app->main($args);
        self::assertSame(1, $status);
    }

    public function testInvalidWrongPipelineNameArgumentException()
    {
        $this->expectOutputRegex(
            "~^pipelines: pipeline 'test/more' unavailable\n~"
        );
        $app = new App(new Streams(null, null, 'php://output'));
        $args = array(
            'pipelines-test',
            '--debug', '--pipeline', 'test/more',
        );
        $status = $app->main($args);
        self::assertSame(1, $status);
    }

    public function testCopyDeployMode()
    {
        $app = new App(new Streams(null, null, null));
        $args = array(
            'pipelines-test',
            '--deploy', 'copy', '--dry-run',
        );
        $status = $app->main($args);
        self::assertSame(0, $status);
    }

    public function testArtifacts()
    {
        $app = new App(new Streams(null, null, null));
        $args = array(
            'pipelines-test',
            '--deploy', 'copy', '--pipeline', 'custom/artifact-tests',
            '--dry-run',
        );
        $status = $app->main($args);
        self::assertSame(0, $status);
    }
}
