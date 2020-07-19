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
        self::assertInstanceOf('Ktomk\Pipelines\Utility\KeepOptions', $keep);

        return $keep;
    }

    /**
     * @depends testCreation
     *
     * @param KeepOptions $keep
     *
     * @throws StatusException
     */
    public function testRun(KeepOptions $keep)
    {
        $actual = $keep->run();
        self::assertInstanceOf(get_class($keep), $actual);
    }

    /**
     * @depends testCreation
     *
     * @param KeepOptions $keep
     */
    public function testHasErrorKeep(KeepOptions $keep)
    {
        self::assertTrue($keep->hasErrorKeep());
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
            self::fail('an expected exception has not been thrown');
        } catch (StatusException $exception) {
            self::assertSame($expected, $exception->getMessage());
            self::assertSame(1, $exception->getCode());
        }
    }

    public function provideParseArgs()
    {
        return array(
            array(array('test-util'), array(false, false)),
            array(array('test-util', '--no-keep'), array(false, false)),
            array(array('test-util', '--keep'), array(false, true)),
            array(array('test-util', '--error-keep'), array(true, false)),
        );
    }

    /**
     * @dataProvider provideParseArgs
     *
     * @param array $argv
     * @param array $expected
     *
     * @throws StatusException
     */
    public function testParse(array $argv, array $expected)
    {
        $args = Args::create($argv);
        $keep = new KeepOptions($args);
        $actual = $keep->parse($args);
        self::assertSame($expected, $actual);
    }
}
