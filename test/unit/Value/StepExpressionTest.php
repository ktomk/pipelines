<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Value;

use Ktomk\Pipelines\TestCase;

/**
 * Class StepExpressionTest
 *
 * @package Ktomk\Pipelines\Value
 * @covers \Ktomk\Pipelines\Value\StepExpression
 */
class StepExpressionTest extends TestCase
{
    public function testCreation()
    {
        $expr = new StepExpression(array());
        $this->addToAssertionCount(1);
    }

    public function provideExpressions()
    {
        return array(
            array(''),
            array('-'),
            array(' , , ,'),
            array(' -, , ,'),
            array(' 1 - ', array('1-')),
            array(' -3 ', array('-3')),
            array('1 -3 ', array('1-3')),
            array(' 1 - 3 5 6', array('1-3', '5', '6')),
            array('1-3', array('1-3')),
            array('1-3 4-6', array('1-3', '4-6')),
            array('1,2,3', array('1', '2', '3')),
            array(',,,3', array('3')),
            array(', , , 3', array('3')),
            array('1 - , 4 - 6 ', array('1-', '4-6')),
        );
    }

    /**
     * @dataProvider provideExpressions
     *
     * @param string $expression
     * @param null|array $expected [optional] array for expected result, null for exception
     */
    public function testCreateFromString($expression, array $expected = null)
    {
        if (null === $expected) {
            $this->expectException('InvalidArgumentException');
        }
        $expr = StepExpression::createFromString($expression);
        self::assertInstanceOf('Ktomk\Pipelines\Value\StepExpression', $expr);
        self::assertSame($expected, $expr->getSegments());
    }

    public function testResolveCountableOutOfBounds()
    {
        $expr = StepExpression::createFromString('1-5');
        $this->expectException('InvalidArgumentException');
        $expr->resolveCountable(array(1));
    }

    public function testResolveCountOutOfBounds()
    {
        $expr = StepExpression::createFromString('1-5');
        $this->expectException('InvalidArgumentException');
        $expr->resolveCount(0);
    }

    public function provideResolvedExpressions()
    {
        return array(
            array('19', array(19)),
            array('20', null),
            array('0', null),
            array('01', null),
            array('0-9', null),
            array('1-0', null),
            array('1-5', range(1, 5)),
            array('5-1', range(5, 1)),
            array('1-3,4-5', range(1, 5)),
            array('1,2,3', range(1, 3)),
            array('3,2,1', range(3, 1)),
            array('-3', range(1, 3)),
            array('15-', range(15, 19)),
            array('-3,15-', array_merge(range(1, 3), range(15, 19))),
            array('15-,-3', array_merge(range(15, 19), range(1, 3))),
        );
    }

    /**
     * @dataProvider provideResolvedExpressions
     *
     * @param string $expression
     * @param null|array $expected [optional] array for expected result, null for exception
     */
    public function testResolveCountable($expression, array $expected = null)
    {
        if (null === $expected) {
            $this->expectException('InvalidArgumentException');
        }

        $actual = StepExpression::createFromString($expression)
            ->resolveCountable(range(1, 19));

        self::assertSame($expected, $actual);
    }

    public function testResolveSteps()
    {
        $steps = $this->createPartialMock('Ktomk\Pipelines\File\Pipeline\Steps', array('count'));
        $steps->expects(self::once())->method('count')->willReturn(1);

        $actual = StepExpression::createFromString('1,1')
            ->resolveSteps($steps);
        self::assertSame(array(null, null), $actual);
    }

    /**
     * @dataProvider provideExpressions
     *
     * @param $expression
     * @param null|array $decomposed
     */
    public function testValidate($expression, array $decomposed = null)
    {
        $expected = null !== $decomposed;
        self::assertSame($expected, StepExpression::validate($expression));
    }
}
