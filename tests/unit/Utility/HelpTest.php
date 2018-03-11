<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Streams;
use PHPUnit\Framework\TestCase;

/**
 * Class HelpTest
 * @covers \Ktomk\Pipelines\Utility\Help
 */
class HelpTest extends TestCase
{
    public function testCreation()
    {
        $streams = new Streams(null, 'php://output');
        $help = new Help($streams);
        $this->assertInstanceOf('Ktomk\Pipelines\Utility\Help', $help);

        return $help;
    }

    /**
     * @param Help $help
     * @depends testCreation
     */
    public function testShowUsage(Help $help)
    {
        $this->expectOutputRegex('~^usage: pipelines \\[<options>...]~');
        $help->showUsage();
    }

    /**
     * @param Help $help
     * @depends testCreation
     */
    public function testShowHelp(Help $help)
    {
        $this->expectOutputRegex('~^usage: pipelines \\[<options>...]~');
        $actual = $help->showHelp();
        $this->assertSame(0, $actual);
    }

    /**
     * @param Help $help
     * @depends testCreation
     */
    public function testShowVersion(Help $help)
    {
        $this->expectOutputRegex('~^pipelines version [^\\n]{5,}$~');
        $actual = $help->showVersion();
        $this->assertSame(0, $actual);
    }
}
