<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Containers;

use Ktomk\Pipelines\TestCase;

/**
 * Class NameBuilderTest
 *
 * @package Ktomk\Pipelines\Runner\Containers
 * @covers \Ktomk\Pipelines\Runner\Containers\NameBuilder
 */
class NameBuilderTest extends TestCase
{
    public function provideSlugifyStrings()
    {
        return array(
            array('a', null, 'a'),
            array("\xC3\xB6", null, ''), # Unicode Character 'LATIN SMALL LETTER O WITH DIAERESIS' (U+00F6)
            array('#', null, ''),
            array('foo#-mixing-food.#3service', null, 'foo-mixing-food-3service'),
            array('33.5 horses on the run', null, 'horses-on-the-run'),
            array('-33.5 horses on the run', null, 'horses-on-the-run'),
            array('it was - (minus!) 33.5 degrees - how that was fun', '_', 'it_was_minus_33.5_degrees_how_that_was_fun'),
            array('-#.#.#.#.#-', null, ''),
        );
    }

    /**
     * @dataProvider provideSlugifyStrings
     *
     * @param string $string
     * @param null|string $replacement
     * @param string $expected
     */
    public function testSlugify($string, $replacement, $expected)
    {
        $actual = NameBuilder::slugify($string, $replacement);
        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider provideSlugifyStrings
     *
     * @param string $string
     * @param null|string $replacement
     * @param string $expected
     */
    public function testSlugifyFallBack($string, $replacement, $expected)
    {
        if ('' === $expected) {
            $expected = 'fall-back';
        }
        $actual = NameBuilder::slugify($string, $replacement, 'fall-back');
        self::assertSame($expected, $actual);
    }

    public function testServiceContainerName()
    {
        $actual = NameBuilder::serviceContainerName(null, null, null);
        self::assertSame('service-unnamed', $actual);
    }

    public function testStepContainerName()
    {
        $actual = NameBuilder::stepContainerName(null, null, null, 'null', null);
        self::assertSame('null-1.no-name.null', $actual);

        $expected = 'prefix.service-redis.app';
        $actual = NameBuilder::serviceContainerName('prefix', 'redis', 'app');
        self::assertSame($expected, $actual);
    }

    public function testStepContainerNameByStep()
    {
        $pipeline = $this->createMock('Ktomk\Pipelines\File\Pipeline');
        $step = $this->createMock('Ktomk\Pipelines\File\Pipeline\Step');
        $step->method('getPipeline')->willReturn($pipeline);

        self::assertSame('null-1.no-name.null', NameBuilder::stepContainerNameByStep($step, 'null', null));
    }
}
