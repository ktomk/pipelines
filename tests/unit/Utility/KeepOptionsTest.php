<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Utility\KeepOptions
 */
class KeepOptionsTest extends TestCase
{
    public function testCreation()
    {
        $args = Args::create(array('test-util', '--error-keep'));
        $keep = KeepOptions::bind($args);
        $this->assertInstanceOf('Ktomk\Pipelines\Utility\KeepOptions', $keep);

        return $keep;
    }

    /**
     * @depends testCreation
     * @param KeepOptions $keep
     * @throws StatusException
     */
    public function testParse(KeepOptions $keep)
    {
        $actual = $keep->run();
        $this->assertInstanceOf(get_class($keep), $actual);
    }

    /**
     * @depends testCreation
     * @param KeepOptions $keep
     */
    public function testHasErrorKeep(KeepOptions $keep)
    {
        $this->assertTrue($keep->hasErrorKeep());
    }

    public function provideExclusivityExceptions()
    {
        return array(
            array(array('--error-keep', '--keep'), '--keep and --error-keep are exclusive'),
            array(array('--no-keep', '--keep'), '--keep and --no-keep are exclusive'),
            array(array('--no-keep', '--error-keep'), '--error-keep and --no-keep are exclusive'),
        );
    }

    /**
     * @param array $arguments
     * @param string $expected
     * @dataProvider provideExclusivityExceptions
     */
    public function testExclusivityException(array $arguments, $expected)
    {
        $args = Args::create(
            array_merge(array('test-util'), $arguments)
        );
        $keep = new KeepOptions($args);

        try {
            $keep->parse($args);
            $this->fail('an expected exception has not been thrown');
        } catch (StatusException $exception) {
            $this->assertSame($expected, $exception->getMessage());
            $this->assertSame(1, $exception->getCode());
        }
    }
}
