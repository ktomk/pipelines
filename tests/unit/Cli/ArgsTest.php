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
        $args = Args::create(array('cmd', '--verbose', '-v', '--', '--operand'));
        $this->assertFalse($args->hasOption('cmd'));
        $this->assertFalse($args->hasOption('f'));
        $this->assertTrue($args->hasOption('verbose'));
        $this->assertTrue($args->hasOption(array('foo', 'v')));
        $this->assertFalse($args->hasOption('operand'));
    }

    public function testOptionConsumption()
    {
        $args = new Args(array('--verbose'));
        $this->assertCount(1, $args->getRemaining());

        $this->assertTrue($args->hasOption(array('v', 'verbose')));
        $this->assertCount(0, $args->getRemaining());
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
     * @param array $arguments
     * @param string $expected first remaining option
     */
    public function testGetFirstRemainingOption(array $arguments, $expected)
    {
        $args = new Args($arguments);
        $this->assertSame($expected, $args->getFirstRemainingOption());
    }

    /**
     * @throws ArgsException
     */
    public function testOptionArgument()
    {
        $args = new Args(array('--prefix', 'value'));
        $actual = $args->getOptionArgument('prefix');
        $this->assertSame('value', $actual);
    }

    /**
     * @throws ArgsException
     */
    public function testOptionalOptionArgument()
    {
        $args = new Args(array('--prefix', 'value'));
        $actual = $args->getOptionArgument('volume', 100);
        $this->assertSame(100, $actual);

        $args = new Args(array('--prefix', 'value', '--', 'operand'));
        $actual = $args->getOptionArgument('volume', 100);
        $this->assertSame(100, $actual);
    }

    /**
     * @expectedException \Ktomk\Pipelines\Cli\ArgsException
     * @expectedExceptionMessage option --volume is not optional
     */
    public function testMandatoryOption()
    {
        $args = new Args(array('--prefix', 'value'));
        $args->getOptionArgument('volume', null, true);
    }

    /**
     * @throws ArgsException
     */
    public function testNonMandatoryOption()
    {
        $args = new Args(array('--prefix', 'value'));
        $this->assertNull($args->getOptionArgument('volume'));
    }

    /**
     * @expectedException \Ktomk\Pipelines\Cli\ArgsException
     * @expectedExceptionMessage option --prefix requires an argument
     */
    public function testMandatoryOptionArgument()
    {
        $args = new Args(array('--prefix'));
        $args->getOptionArgument('prefix', 100);
    }

    /**
     * @expectedException \Ktomk\Pipelines\Cli\ArgsException
     * @expectedExceptionMessage option --prefix requires an argument
     */
    public function testMandatoryOptionArgumentWithParameters()
    {
        $args = new Args(array('--prefix', '--'));
        $args->getOptionArgument('prefix', 100);
    }
}
