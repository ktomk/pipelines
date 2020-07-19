<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Cli\Args
 */
class ArgsTest extends TestCase
{
    public function testCreation()
    {
        $args = new Args(array('test'));
        self::assertInstanceOf('Ktomk\Pipelines\Cli\Args', $args);

        $args = Args::create(array('test'));
        self::assertInstanceOf('Ktomk\Pipelines\Cli\Args', $args);
    }

    public function testMissingCommand()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('There must be at least one argument (the command name)');

        Args::create(array());
    }

    public function testUtility()
    {
        $args = Args::create(array('cmd'));
        self::assertSame('cmd', $args->getUtility());
    }

    public function testHasOption()
    {
        $args = Args::create(array('cmd', '--verbose', '-v', '--', '--operand'));
        self::assertFalse($args->hasOption('cmd'));
        self::assertFalse($args->hasOption('f'));
        self::assertTrue($args->hasOption('verbose'));
        self::assertTrue($args->hasOption(array('foo', 'v')));
        self::assertFalse($args->hasOption('operand'));
    }

    public function testMapOption()
    {
        $args = Args::create(array('cmd', '--verbose', '-v', '--', '--operand'));
        $actual = $args->mapOption(
            array('verbose' => null),
            function ($option, $parameter) {
                return $option;
            }
        );
        self::assertSame(array('verbose' => array(0 => true, 1 => 'verbose')), $actual);
    }

    public function testOptionConsumption()
    {
        $args = new Args(array('--verbose'));
        self::assertCount(1, $args->getRemaining());

        self::assertTrue($args->hasOption(array('v', 'verbose')));
        self::assertCount(0, $args->getRemaining());
    }

    public function provideFirstRemainingOptions()
    {
        return array(
            array(array('--verbose'), '--verbose'),
            array(array('test', '--verbose'), '--verbose'),
            array(array('verbose'), null),
            array(array('--'), null),
            array(array('--', '--me-is-parameter'), null),
            array(array('-'), null),
            array(array(''), null),
            array(array('', '--force'), '--force'),
        );
    }

    /**
     * @dataProvider provideFirstRemainingOptions
     *
     * @param array $arguments
     * @param string $expected first remaining option
     */
    public function testGetFirstRemainingOption(array $arguments, $expected)
    {
        $args = new Args($arguments);
        self::assertSame($expected, $args->getFirstRemainingOption());
    }

    /**
     * @throws ArgsException
     */
    public function testGetOptionArgument()
    {
        $args = new Args(array('--prefix', 'value'));
        $actual = $args->getOptionArgument('prefix');
        self::assertSame('value', $actual);
    }

    /**
     * @throws ArgsException
     */
    public function testGetOptionArgumentOptionalOption()
    {
        $args = new Args(array('--prefix', 'value'));
        $actual = $args->getOptionArgument('volume', 100);
        self::assertSame(100, $actual);

        $args = new Args(array('--prefix', 'value', '--', 'operand'));
        $actual = $args->getOptionArgument('volume', 100);
        self::assertSame(100, $actual);
    }

    public function testGetOptionOptionalArgument()
    {
        $args = new Args(array('--foo', '--bar=', '--baz=foo'));
        self::assertNull($args->getOptionOptionalArgument('faux', true));
        self::assertTrue($args->getOptionOptionalArgument('foo', true));
        self::assertSame('', $args->getOptionOptionalArgument('bar', true));
        self::assertSame('foo', $args->getOptionOptionalArgument('baz', true));
    }

    /**
     * @throws ArgsException
     */
    public function testMandatoryOption()
    {
        $this->expectException('Ktomk\Pipelines\Cli\ArgsException');
        $this->expectExceptionMessage('option --volume is not optional');

        $args = new Args(array('--prefix', 'value'));
        $args->getOptionArgument('volume', null, true);
    }

    /**
     * @throws ArgsException
     */
    public function testNonMandatoryOption()
    {
        $args = new Args(array('--prefix', 'value'));
        self::assertNull($args->getOptionArgument('volume'));
    }

    /**
     * @throws ArgsException
     */
    public function testMandatoryOptionArgument()
    {
        $this->expectException('Ktomk\Pipelines\Cli\ArgsException');
        $this->expectExceptionMessage('option --prefix requires an argument');

        $args = new Args(array('--prefix'));
        $args->getOptionArgument('prefix', 100);
    }

    /**
     * @throws ArgsException
     */
    public function testMandatoryOptionArgumentWithParameters()
    {
        $this->expectException('Ktomk\Pipelines\Cli\ArgsException');
        $this->expectExceptionMessage('option --prefix requires an argument');

        $args = new Args(array('--prefix', '--'));
        $args->getOptionArgument('prefix', 100);
    }

    /**
     * @throws ArgsException
     */
    public function testGetStringOptionArgumentThrows()
    {
        $args = new Args(array('--prefix', '--'));

        self::assertSame('default', $args->getStringOptionArgument('suffix', 'default'));

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('default value must be string, integer given');

        $args->getStringOptionArgument('prefix', 100);
    }
}
