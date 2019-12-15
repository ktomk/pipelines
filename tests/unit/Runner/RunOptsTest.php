<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\TestCase;

/**
 * Class RunOptsTest
 *
 * @covers \Ktomk\Pipelines\Runner\RunOpts
 */
class RunOptsTest extends TestCase
{
    public function testCreation()
    {
        $opts = RunOpts::create('');
        $this->assertInstanceOf('Ktomk\Pipelines\Runner\RunOpts', $opts);

        return $opts;
    }

    /**
     * @depends testCreation
     * @param RunOpts $opts
     */
    public function testPrefix(RunOpts $opts)
    {
        self::assertIsString($opts->getPrefix());
        $opts->setPrefix('foo');
        $this->assertSame('foo', $opts->getPrefix());
    }
}
