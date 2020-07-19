<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli\Args;

use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Cli\Args\OptionMatcher
 */
class OptionMatcherTest extends TestCase
{
    public function testCreate()
    {
        self::assertTrue(OptionMatcher::create('user')->match('--user=1000:1000'));
        self::assertFalse(OptionMatcher::create('user')->match('--foo-user=1000:1000'));
    }

    /**
     * @return array
     */
    public function provideOptionArgMatches()
    {
        return array(
            array(false, '', '', false),
            array(false, '', '', true),
            array(false, 'n', '', false),
            array(false, 'n', '', true),
            array(false, '', '--option', false),
            array(false, '', '--option', true),
            array(false, '', 'option', false),
            array(false, '', 'option', true),
            array(false, 'option', 'option', false),
            array(false, 'option', 'option', true),
            array(false, 'option', '--', false),
            array(false, 'option', '--', true),
            array(false, 'option', '--foo', false),
            array(false, 'option', '--foo', true),
            array(false, 'option', '-option', false),
            array(false, 'option', '-option', true),
            array(true, 'option', '--option', false),
            array(true, 'option', '--option', true),
            array(false, 'option', '--=', true),
            array(true, 'option', '--option=', true),
            array(true, 'option', '--option=bar', true),
            array(false, 'b', '-fo', false),
            array(false, 'b', '-fo=', false),
            array(false, 'b', '-fo=', true),
            array(false, 'b', '-fo=ar', false),
            array(false, 'b', '-fo=ba', true),
            array(true, 'b', '-fo=ba', false),
            array(true, 'b', '-bar', false),
            array(true, 'b', '-bar', false),
            array(true, 'b', '-arb=', true),
            array(true, 'b', '-arb=bar', false),
            array(true, 'b', '-arb=bar', true),
        );
    }

    /**
     * @dataProvider provideOptionArgMatches
     *
     * @param bool $expected
     * @param string $option
     * @param string $arg
     * @param null|bool $equals
     */
    public function testMatchOptionArg($expected, $option, $arg, $equals)
    {
        $actual = OptionMatcher::matchOptionArg($option, $arg, $equals);
        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider provideOptionArgMatches
     *
     * @param bool $expected
     * @param string|string[] $option
     * @param string $arg
     * @param null|bool $equals
     */
    public function testMatch($expected, $option, $arg, $equals)
    {
        $matcher = new OptionMatcher($option, $equals);
        self::assertSame($expected, $matcher->match($arg));
    }
}
