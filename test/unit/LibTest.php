<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

/**
 * @covers \Ktomk\Pipelines\Lib
 */
class LibTest extends TestCase
{
    public function testId()
    {
        self::assertSame(1, Lib::id(1));
    }

    public function testRSet()
    {
        $ref = 'a';
        self::assertSame($ref, Lib::r($ref, null));
    }

    public function testRUnset()
    {
        $ref = null;
        self::assertSame('a', Lib::r($ref, 'a'));
    }

    public function testVSet()
    {
        $variable = false;
        Lib::v($variable, true);
        self::assertFalse($variable);
    }

    public function testVUnset()
    {
        Lib::v($variable, true);
        self::assertTrue($variable);
    }

    public function testGenerateUuid()
    {
        $actual = Lib::generateUuid();
        $pattern = '/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?'
            . '[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i';
        self::assertMatchesRegularExpression($pattern, $actual);
    }

    public function testCmd()
    {
        $actual = Lib::cmd('foo', array('bar', 'baz'));
        $expected = 'foo bar baz';
        self::assertIsString($actual);
        self::assertSame($actual, $expected);
    }

    public function testCmdArgumentMerging()
    {
        $actual = Lib::cmd('cmd', array('-a', array('-b', 'c')));
        $expected = 'cmd -a -b c';
        self::assertSame($actual, $expected);
    }

    public function provideQuoteArgs()
    {
        return array(
            array('abc', 'abc'),
            array(' ', "' '"),
            array("'", "\\'"),
            array("''", "\\'\\'"),
            array("sally's o'hara", "sally\\''s o'\\'hara"),
            array('', "''"),
            array("\n", "'\n'"),
        );
    }

    /**
     * @dataProvider provideQuoteArgs
     *
     * @param mixed $argument
     * @param mixed $expected
     */
    public function testQuoteArg($argument, $expected)
    {
        $actual = Lib::quoteArg($argument);
        self::assertSame($expected, $actual);
    }

    /**
     *
     */
    public function testLines()
    {
        $atEnd = "1\n2\n3\n";

        $lines = Lib::lines($atEnd);

        self::assertSame(array('1', '2', '3'), $lines);
    }

    public function testMerge()
    {
        self::assertSame(array(1, 3, 4), Lib::merge(1, array(3, 4)));
    }

    public function testMergeEmpty()
    {
        self::assertSame(array(), Lib::merge());
    }

    public function testMergeArrayEmpty()
    {
        self::assertSame(array(), Lib::mergeArray(array()));
    }

    public function testIterEach()
    {
        $array = array('a' => 'b');
        self::assertSame(1, Lib::iterEach(function ($value, $key, $iter) use (&$result) {
            $result[] = func_get_args();
        }, $array));
        list(list($value, $key, $iter)) = $result;
        self::assertSame($array['a'], $value, 'value');
        self::assertSame('a', $key, 'key');
        self::assertSame($array, $iter, 'iter');
    }

    public function testIterMap()
    {
        self::assertSame(array('a', 'b'), Lib::iterMap('trim', array(' a ', ' b ')));
    }

    public function testPhpBinary()
    {
        self::assertIsString(Lib::phpBinary());
    }

    /**
     */
    public function testArrayChunkByStringLengthThrowsException()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('maximum length of 2 is too little');

        Lib::arrayChunkByStringLength(array('test'), 2);
    }

    public function testArrayChunkByStringLength()
    {
        $expected = array(array('test'), array('fest'));
        $actual = Lib::arrayChunkByStringLength(array('test', 'fest'), 4);
        self::assertSame($expected, $actual);
    }

    public function testEnvServerSuperglobalFiltering()
    {
        $server = $_SERVER;

        $server['foo=bar'] = 'baz';
        self::assertArrayHasKey('HOME', $server, 'pre-condition');
        self::assertArrayHasKey('argc', $server, 'pre-condition');
        self::assertArrayHasKey('REQUEST_TIME', $server, 'pre-condition');

        $env = Lib::env($server);

        self::assertArrayNotHasKey('foo=bar', $env, 'behavioral assertion');
        self::assertArrayHasKey('HOME', $server, 'behavioral assertion');
        self::assertArrayNotHasKey('argc', $env, 'behavioral assertion');
        self::assertArrayNotHasKey('REQUEST_TIME', $env, 'behavioral assertion');
    }

    public function testEmptyCoalesce()
    {
        self::assertNull(Lib::emptyCoalesce());
        self::assertNull(Lib::emptyCoalesce(false));
        self::assertTrue(Lib::emptyCoalesce(null, false, '0', '', array(), true));
    }
}
