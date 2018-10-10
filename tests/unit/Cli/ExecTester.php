<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

/**
 * Emulate execution of command line utilities in tests
 */
class ExecTester extends Exec
{
    /**
     * @var TestCase
     */
    private $testCase;

    private $expects = array();

    /**
     * ExecTester constructor.
     *
     * @param TestCase $testCase
     */
    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
        parent::__construct();
        parent::setActive(false);
    }

    /**
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function __destruct()
    {
        $testCase = $this->testCase;
        $testCase->addToAssertionCount(1);
        if (count($this->expects)) {
            $testCase::fail(
                sprintf(
                    'Failed assertion that expected number of exec\'s were done (%d left)',
                    count($this->expects)
                )
            );
        }
    }

    /**
     * @param TestCase $testCase
     * @return ExecTester
     */
    public static function create(TestCase $testCase)
    {
        return new self($testCase);
    }

    /**
     * @param string $method
     * @param string $command
     * @param callable|int|string $context
     * @return $this
     */
    public function expect($method, $command, $context = 0)
    {
        $this->expects[] = array($method, $command, $context);

        return $this;
    }

    /**
     * @param $command
     * @param array $arguments
     * @param null $out
     * @param null $err
     * @throws \PHPUnit\Framework\AssertionFailedError
     * @throws UnexpectedValueException
     * @return $this|int
     */
    public function capture($command, array $arguments, &$out = null, &$err = null)
    {
        return $this->dealInvokeExpectation(__FUNCTION__, $command, $arguments, $out, $err);
    }

    /**
     * @param string $command
     * @param array $arguments
     * @throws \PHPUnit\Framework\AssertionFailedError
     * @throws UnexpectedValueException
     * @return int|mixed
     */
    public function pass($command, array $arguments)
    {
        return $this->dealInvokeExpectation(__FUNCTION__, $command, $arguments);
    }

    /**
     * @param bool $active
     * @throws \BadMethodCallException
     */
    public function setActive($active)
    {
        if (false === (bool)$active) {
            return;
        }

        throw new BadMethodCallException(
            'This exec tester can not be used in tests that require setting it to active.'
        );
    }

    /**
     * @param string $method
     * @param string $command
     * @param array $arguments
     * @param null $out
     * @param null $err
     * @throws UnexpectedValueException
     * @throws \PHPUnit\Framework\AssertionFailedError
     * @return int|mixed
     */
    private function dealInvokeExpectation($method, $command, array $arguments, &$out = null, &$err = null)
    {
        $current = array_shift($this->expects);
        $testCase = $this->testCase;
        if (null === $current) {
            $testCase::fail(
                sprintf(
                    "Exec tester violation: %s() with command '%s' called with no more expectations",
                    $method,
                    $command
                )
            );
        }

        list (
            $expectedMethod,
            $expectedCommand,
            $context,
            ) = $current;

        $testCase::assertSame(
            $expectedMethod,
            $method,
            sprintf("Method on exec mismatch with command '%s'", $command)
        );

        $testCase::assertSame(
            $expectedCommand,
            $command,
            sprintf("Command on exec mismatch with method '%s'", $method)
        );

        if (is_int($context)) {
            return $context;
        }

        if (is_string($context)) {
            $out = $context;

            return 0;
        }

        if (is_callable($context)) {
            return call_user_func_array(
                $context,
                array($command, $arguments, &$out, &$err)
            );
        }

        throw new UnexpectedValueException('Invalid context');
    }
}
