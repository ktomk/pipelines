<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;

/**
 * Emulate execution of command line utilities in tests
 */
class ExecTester extends Exec
{
    /**
     * @var TestCase
     */
    private $testCase;

    /**
     * @param TestCase $testCase
     * @return ExecTester
     */
    public static function create(TestCase $testCase)
    {
        return new self($testCase);
    }

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

    private $expects = array();

    /**
     * @param string $method
     * @param string $command
     * @param int|string|callable $context
     * @return $this
     */
    public function expect($method, $command, $context = 0)
    {
        $this->expects[] = array($method, $command, $context);
        return $this;
    }

    private function dealInvokeExpectation($method, $command, array $arguments, &$out = null, &$err = null)
    {
        $current = array_shift($this->expects);
        if ($current === null) {
            $this->testCase->fail(
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

        $this->testCase->assertSame(
            $expectedMethod,
            $method,
            sprintf("Method on exec mismatch with command '%s'", $command)
        );

        $this->testCase->assertSame(
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

        throw new \UnexpectedValueException('Invalid context');
    }

    /**
     * @param $command
     * @param array $arguments
     * @param null $out
     * @param null $err
     * @return $this|int
     */
    public function capture($command, array $arguments, &$out = null, &$err = null)
    {
        return $this->dealInvokeExpectation(__FUNCTION__, $command, $arguments, $out, $err);
    }

    public function pass($command, array $arguments)
    {
        return $this->dealInvokeExpectation(__FUNCTION__, $command, $arguments);
    }

    /**
     * @param bool $active
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

    public function __destruct()
    {
        $this->testCase->addToAssertionCount(1);
        if (count($this->expects)) {
            $this->testCase->fail(
                sprintf(
                    'Failed assertion that expected number of exec\'s were done (%d left)',
                    count($this->expects)
                )
            );
        }
    }
}
