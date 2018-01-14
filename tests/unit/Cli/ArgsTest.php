<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Ktomk\Pipelines\Cli\Args
 */
class ArgsTest extends TestCase
{
    public function testCreation()
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
    public function testMissingCommand()
    {
        Args::create(array());
    }

    public function testUtility()
    {
        $args = Args::create(array('cmd'));
        $this->assertSame('cmd', $args->getUtility());
    }

    public function testHasOption()
    {
        $args = new Args(array('cmd', '--verbose', '-v', '--', "--operand"));
        $this->assertFalse($args->hasOption('cmd'));
        $this->assertFalse($args->hasOption('f'));
        $this->assertTrue($args->hasOption('verbose'));
        $this->assertTrue($args->hasOption(array('foo', 'v')));
        $this->assertFalse($args->hasOption('operand'));
    }

    public function testOptionConsumption()
    {
        $args = new Args(array('cmd', '--verbose'));
        $this->assertCount(1, $args->getRemaining());

        $this->assertTrue($args->hasOption(array('v', 'verbose')));
        $this->assertCount(0, $args->getRemaining());
    }

    public function provideFirstRemainingOptions()
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
    public function testGetFirstRemainingOption($arguments, $expected)
    {
        $args = new Args($arguments);
        $this->assertSame($expected, $args->getFirstRemainingOption());
    }

    public function testOptionArgument()
    {
        $args = new Args(array('cmd', '--prefix', 'value'));
        $actual = $args->getOptionArgument('prefix');
        $this->assertSame('value', $actual);
    }

    public function testOptionalOptionArgument()
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
    public function testMandatoryOption()
    {
        $args = new Args(array('cmd', '--prefix', 'value'));
        $args->getOptionArgument('volume', null, true);
    }

    public function testNonMandatoryOption()
    {
        $args = new Args(array('cmd', '--prefix', 'value'));
        $this->assertNull($args->getOptionArgument('volumne', null));
    }

    /**
     * @expectedException \Ktomk\Pipelines\Cli\ArgsException
     * @expectedExceptionMessage error: option 'prefix' requires an argument
     */
    public function testMandatorOptionArgument()
    {
        $args = new Args(array('cmd', '--prefix'));
        $args->getOptionArgument('prefix', 100);
    }

    /**
     * @expectedException \Ktomk\Pipelines\Cli\ArgsException
     * @expectedExceptionMessage error: option 'prefix' requires an argument
     */
    public function testMandatorOptionArgumentWithParameters()
    {
        $args = new Args(array('cmd', '--prefix', '--'));
        $args->getOptionArgument('prefix', 100);
    }

    public function provideInvalidOptions()
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
    public function testInvalidOptions($option)
    {
        $args = new Args(array(''));
        $args->hasOption($option);
    }
}
