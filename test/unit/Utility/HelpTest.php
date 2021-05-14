<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Cli\Streams;
use Ktomk\Pipelines\TestCase;

/**
 * Class HelpTest
 *
 * @covers \Ktomk\Pipelines\Utility\Help
 */
class HelpTest extends TestCase
{
    public function testCreation()
    {
        $streams = new Streams(null, 'php://output');
        $help = new Help($streams);
        self::assertInstanceOf('Ktomk\Pipelines\Utility\Help', $help);

        return $help;
    }

    /**
     * @param Help $help
     * @depends testCreation
     */
    public function testShowUsage(Help $help)
    {
        $this->expectOutputRegex('~^usage: pipelines \\[<options>] ~');
        $help->showUsage();
    }

    /**
     * @param Help $help
     * @depends testCreation
     */
    public function testShowHelp(Help $help)
    {
        $this->expectOutputRegex('~^usage: pipelines \\[<options>] ~');
        $actual = $help->showHelp();
        self::assertSame(0, $actual);
    }

    /**
     * @param Help $help
     * @depends testCreation
     */
    public function testShowVersion(Help $help)
    {
        $this->expectOutputRegex('~^pipelines version [^\\n]{5,}$~');
        $actual = $help->showVersion();
        self::assertSame(0, $actual);
    }

    /**
     * @param Help $help
     * @depends testCreation
     */
    public function testRunHelp(Help $help)
    {
        $this->expectOutputRegex('~^usage: pipelines ~');
        $args = new Args(array('cmd', '--help'));

        try {
            $help->run($args);
            self::fail('an expected exception has not been thrown');
        } catch (StatusException $e) {
            self::assertSame(0, $e->getCode());
        }
    }

    /**
     * @param Help $help
     * @depends testCreation
     */
    public function testRunVersion(Help $help)
    {
        $this->expectOutputRegex(
            "{^pipelines version (@\\.@\\.@|[a-f0-9]{7}|\\d+\\.\\d+\\.\\d+)(\\+\\d+-g[a-f0-9]{7})?(\\+dirty)?\n}"
        );
        $args = new Args(array('cmd', '--version'));

        try {
            $help->run($args);
            self::fail('an expected exception has not been thrown');
        } catch (StatusException $e) {
            self::assertSame(0, $e->getCode());
        }
    }

    /**
     * @param Help $help
     *
     * @throws StatusException
     * @depends testCreation
     */
    public function testRun(Help $help)
    {
        $args = new Args(array('cmd', '--dev-null-da-place-to-be'));
        $help->run($args);
        $this->addToAssertionCount(1);
    }
}
