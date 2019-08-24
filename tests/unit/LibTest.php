<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Lib
 */
class LibTest extends TestCase
{
    public function testRSet()
    {
        $ref = 'a';
        $this->assertSame($ref, Lib::r($ref, null));
    }

    public function testRUnset()
    {
        $ref = null;
        $this->assertSame('a', Lib::r($ref, 'a'));
    }

    public function testVSet()
    {
        $variable = false;
        Lib::v($variable, true);
        $this->assertFalse($variable);
    }

    public function testVUnset()
    {
        Lib::v($variable, true);
        $this->assertTrue($variable);
    }

    public function testGenerateUuid()
    {
        $actual = Lib::generateUuid();
        $pattern = '/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?' .
            '[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i';
        $this->assertRegExp($pattern, $actual);
    }

    public function testCmd()
    {
        $actual = Lib::cmd('foo', array('bar', 'baz'));
        $expected = 'foo bar baz';
        $this->assertInternalType('string', $actual);
        $this->assertSame($actual, $expected);
    }

    public function testCmdArgumentMerging()
    {
        $actual = Lib::cmd('cmd', array('-a', array('-b', 'c')));
        $expected = 'cmd -a -b c';
        $this->assertSame($actual, $expected);
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
        );
    }

    /**
     * @dataProvider provideQuoteArgs
     * @param mixed $argument
     * @param mixed $expected
     */
    public function testQuoteArg($argument, $expected)
    {
        $actual = Lib::quoteArg($argument);
        $this->assertSame($expected, $actual);
    }

    /**
     *
     */
    public function testLines()
    {
        $atEnd = "1\n2\n3\n";

        $lines = Lib::lines($atEnd);

        $this->assertSame(array('1', '2', '3'), $lines);
    }

    public function testMerge()
    {
        $this->assertSame(array(1,3,4), Lib::merge(1, array(3,4)));
    }

    public function testMergeEmpty()
    {
        $this->assertSame(array(), Lib::merge());
    }

    public function provideBracePattern()
    {
        return array(
            array(array(''), ''),
            array(array('{}'), '{}'),
            'no duplicates' => array(array(''), '{,}'),
            'no duplicates 2' => array(array('ab'), 'a{,}b'),
            array(array('acb', 'adb'), 'a{c,d}b'),
            'hangover left' => array(array('a{cb', 'a{db'), 'a{{c,d}b'),
            'hangover right' => array(array('ac}b', 'ad}b'), 'a{c,d}}b'),
            array(array('abe', 'ace', 'ade'), 'a{b,{c,d}}e'),
            'brace' => array(array('ab', 'ac'), 'a{b,c}'),
            'escaped brace' => array(array('a{b,c}'), "a\\{b,c}"),
            'escaped comma' => array(array('a,', 'ab'), "a{\\,,b}"),
            'multiple' => array(
                array('abdh', 'abefh', 'abgh', 'abcdh', 'abcefh', 'abcgh'),
                'ab{,c}{d,{ef,g}}h'
            )
        );
    }

    /**
     * @param $subject
     * @param $expected
     * @dataProvider provideBracePattern
     */
    public function testExpandBrace($expected, $subject)
    {
        $this->assertSame($expected, Lib::expandBrace($subject), $subject);
    }

    public function testPhpBinary()
    {
        $this->assertInternalType('string', Lib::phpBinary());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage maximum length of 2 is too little
     */
    public function testArrayChunkByStringLengthThrowsException()
    {
        Lib::arrayChunkByStringLength(array('test'), 2);
    }

    public function testArrayChunkByStringLength()
    {
        $expected = array(array('test'), array('fest'));
        $actual = Lib::arrayChunkByStringLength(array('test', 'fest'), 4);
        $this->assertSame($expected, $actual);
    }

    public function testEnvServerSuperglobalFiltering()
    {
        $server = $_SERVER;

        $server['foo=bar'] = 'baz';
        $this->assertArrayHasKey('HOME', $server, 'pre-condition');
        $this->assertArrayHasKey('argc', $server, 'pre-condition');
        $this->assertArrayHasKey('REQUEST_TIME', $server, 'pre-condition');

        $env = Lib::env($server);

        $this->assertArrayNotHasKey('foo=bar', $env, 'behavioral assertion');
        $this->assertArrayHasKey('HOME', $server, 'behavioral assertion');
        $this->assertArrayNotHasKey('argc', $env, 'behavioral assertion');
        $this->assertArrayNotHasKey('REQUEST_TIME', $env, 'behavioral assertion');
    }
}
