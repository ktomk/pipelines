<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Ktomk\Pipelines\Cli\Args
 */
class ArgsTest extends TestCase
{
    function testCreation()
    {
        $args = new Args(array('test'));
        $this->assertInstanceOf('Ktomk\Pipelines\Cli\Args', $args);

        $args = Args::create(array('test'));
        $this->assertInstanceOf('Ktomk\Pipelines\Cli\Args', $args);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage There must be at least one argument (the command name)
     */
    function testMissingCommand()
    {
        Args::create(array());
    }

    function testUtility()
    {
        $args = Args::create(array('cmd'));
        $this->assertSame('cmd', $args->getUtility());
    }

    function testHasOption()
    {
        $args = new Args(array('cmd', '--verbose', '-v', '--', "--operand"));
        $this->assertFalse($args->hasOption('cmd'));
        $this->assertFalse($args->hasOption('f'));
        $this->assertTrue($args->hasOption('verbose'));
        $this->assertTrue($args->hasOption(array('foo', 'v')));
        $this->assertFalse($args->hasOption('operand'));
    }

    function testOptionConsumption()
    {
        $args = new Args(array('cmd', '--verbose'));
        $this->assertCount(1, $args->getRemaining());

        $this->assertTrue($args->hasOption(array('v', 'verbose')));
        $this->assertCount(0, $args->getRemaining());
    }

    function provideFirstRemainingOptions()
    {
        return array(
            array(array('cmd', '--verbose'), '--verbose'),
            array(array('cmd', 'test', '--verbose'), '--verbose'),
            array(array('cmd', 'verbose'), null),
            array(array('cmd', '--'), null),
            array(array('cmd', '--', '--me-is-parameter'), null),
            array(array('cmd', '-'), null),
            array(array('cmd', ''), null),
            array(array('cmd', '', '--force'), '--force'),
        );
    }

    /**
     * @dataProvider provideFirstRemainingOptions
     */
    function testGetFirstRemainingOption($arguments, $expected)
    {
        $args = new Args($arguments);
        $this->assertSame($expected, $args->getFirstRemainingOption());
    }

    function testOptionArgument()
    {
        $args = new Args(array('cmd', '--prefix', 'value'));
        $actual = $args->getOptionArgument('prefix');
        $this->assertSame('value', $actual);
    }

    function testOptionalOptionArgument()
    {
        $args = new Args(array('cmd', '--prefix', 'value'));
        $actual = $args->getOptionArgument('volume', 100);
        $this->assertSame(100, $actual);

        $args = new Args(array('cmd', '--prefix', 'value', '--', 'operand'));
        $actual = $args->getOptionArgument('volume', 100);
        $this->assertSame(100, $actual);
    }

    /**
     * @expectedException \Ktomk\Pipelines\Cli\ArgsException
     * @expectedExceptionMessage error: option 'volume' is not optional
     */
    function testMandatoryOption()
    {
        $args = new Args(array('cmd', '--prefix', 'value'));
        $args->getOptionArgument('volume', null, true);
    }

    function testNonMandatoryOption()
    {
        $args = new Args(array('cmd', '--prefix', 'value'));
        $this->assertNull($args->getOptionArgument('volumne', null));
    }

    /**
     * @expectedException \Ktomk\Pipelines\Cli\ArgsException
     * @expectedExceptionMessage error: option 'prefix' requires an argument
     */
    function testMandatorOptionArgument()
    {
        $args = new Args(array('cmd', '--prefix'));
        $args->getOptionArgument('prefix', 100);
    }

    /**
     * @expectedException \Ktomk\Pipelines\Cli\ArgsException
     * @expectedExceptionMessage error: option 'prefix' requires an argument
     */
    function testMandatorOptionArgumentWithParameters()
    {
        $args = new Args(array('cmd', '--prefix', '--'));
        $args->getOptionArgument('prefix', 100);
    }

    function provideInvalidOptions()
    {
        return array(
            array(''),
            array(' '),
            array('-'),
            array('foo bar'),
            array('-5000'),
            array('?'),
            array('!'),
            array('='),
            array('.'),
        );
    }

    /**
     * @dataProvider provideInvalidOptions
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage invalid option '
     */
    function testInvalidOptions($option)
    {
        $args = new Args(array(''));
        $args->hasOption($option);
    }
}
