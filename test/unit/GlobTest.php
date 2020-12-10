<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

/**
 * @covers \Ktomk\Pipelines\Glob
 */
class GlobTest extends TestCase
{
    /**
     * @var array
     */
    private $levels;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        $this->levels = array(
            # single asterisk matching
            'level-1' => array(
                'equality' => array('master', 'master', true),
                'globally' => array('*', 'master', true),
                'partially' => array('*ter', 'master', true),
                'emptily' => array('*master', 'master', true),
                'emptily suffix' => array('master*', 'master', true),
                'emptily in between' => array('mas*ter*', 'master', true),
                'partially in between' => array('ma*er*', 'master', true),
                'multi-partially in between' => array('m*s*r', 'master', true),
                array('*/master', 'feature/master', true),
                array('*/master', 'feature/minor', false),
                array('feature/*', 'feature/master', true),
                array('feature/*', 'hotfix/minor', false),
                array('feature/bb-123-fix-links', 'feature/minor', false),
                array('feature/bb-123-fix-links', 'feature/bb-123-fix-links', true),
                'version-tag-test' => array('v?*.*?.?*', 'v0.0.0', true),
            ),
            # differentiation between single and double asterisk
            'level-2' => array(
                '* not match dir separator' => array('*', 'feature/blue', false),
                '** matches' => array('**', 'feature/blue', true),
                '** partially' => array('**ue', 'feature/blue', true),
                '** partially no match' => array('**zz', 'feature/blue', false),
            ),
            # brace support
            'level-3' => array(
                'as seen t1' => array('{foo,bar}', 'foo', true),
                'as seen t2' => array('{foo,bar}', 'bar', true),
                'as seen t3' => array('{foo,bar}', 'foo,bar', false),
                'braces {,} do match' => array('feature/{red,blue}', 'feature/blue', true),
                'empty braces not' => array('{}', '{}', true),
                'recursive' => array('{f{,o,{oo,o}},bar}', 'foo', true),
                'recursive end' => array('{f{,o,{oo,o}},bar}', 'bar', true),
            ),
        );

        parent::__construct($name, $data, $dataName);
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
                'ab{,c}{d,{ef,g}}h',
            ),
            'src-src/**/*.php-test-1' => array(
                array('src/*/*.php', 'src/*.php'),
                'src/{*/,}*.php',
            ),
            'src-src/**/*.php-test-2' => array(
                array('src/*/*/*.php', 'src/*.php', 'src/*/*.php'),
                'src/{{*/,}*/,}*.php',
            ),
            'src-src/**/*.php-test-2-reverse-bug' => array(
                array('src/*/*.php', 'src/*.php'),
                'src/{*/,{*/,}}*.php',
            ),
        );
    }

    public function provideMatchPatternLevel1()
    {
        return $this->levels['level-1'];
    }

    public function provideMatchPatternLevel2()
    {
        return $this->levels['level-2'];
    }

    public function provideMatchPatternLevel3()
    {
        return $this->levels['level-3'];
    }

    /**
     * @dataProvider provideMatchPatternLevel1
     *
     * @param mixed $pattern
     * @param mixed $subject
     * @param mixed $expected
     */
    public function testMatchLevel1($pattern, $subject, $expected)
    {
        $this->assert($pattern, $subject, $expected);
    }

    /**
     * @dataProvider provideMatchPatternLevel2
     *
     * @param mixed $pattern
     * @param mixed $subject
     * @param mixed $expected
     */
    public function testMatchLevel2($pattern, $subject, $expected)
    {
        $this->assert($pattern, $subject, $expected);
    }

    /**
     * @dataProvider provideMatchPatternLevel3
     *
     * @param mixed $pattern
     * @param mixed $subject
     * @param mixed $expected
     */
    public function testMatchLevel3($pattern, $subject, $expected)
    {
        $this->assert($pattern, $subject, $expected);
    }

    /**
     * @param $expected
     * @param $subject
     * @dataProvider provideBracePattern
     */
    public function testExpandBrace($expected, $subject)
    {
        self::assertSame($expected, Glob::expandBrace($subject), $subject);
    }

    /**
     * @param string $pattern
     * @param string $subject
     * @param bool $expected
     */
    private function assert($pattern, $subject, $expected)
    {
        $actual = Glob::match($pattern, $subject);
        self::assertSame($expected, $actual, sprintf(
            "pattern '%s' %s subject '%s'",
            $pattern,
            $expected ? 'matches' : 'mismatches',
            $subject
        ));
    }
}
