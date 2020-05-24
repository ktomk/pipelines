<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use PHPUnit\Framework\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as PhpunitTestCase;
use PHPUnit\Util\InvalidArgumentHelper;

/**
 * UnitTestCase
 *
 * Base test case for pipelines test-suite, basically a shim
 * for older/newer phpunit versions.
 *
 * @coversNothing
 */
class TestCase extends PhpunitTestCase
{
    private $expectedException;
    private $expectedExceptionMessage;

    public static function assertIsArray($actual, $message = '')
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::assertIsArray($actual, $message);

            return;
        }

        self::assertInternalType('array', $actual, $message);
    }

    public static function assertIsBool($actual, $message = '')
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::assertIsBool($actual, $message);

            return;
        }

        self::assertInternalType('bool', $actual, $message);
    }

    public static function assertIsCallable($actual, $message = '')
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::assertIsCallable($actual, $message);

            return;
        }

        self::assertInternalType('callable', $actual, $message);
    }

    public static function assertIsInt($actual, $message = '')
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::assertIsInt($actual, $message);

            return;
        }

        self::assertInternalType('integer', $actual, $message);
    }

    public static function assertIsString($actual, $message = '')
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::assertIsString($actual, $message);

            return;
        }

        self::assertInternalType('string', $actual, $message);
    }

    /**
     * assertContains() with string haystacks >>> assertStringContainsString
     *
     * assertStringContainsString was added in Phpunit 8 to lighten up usage
     * of assertContains power-factory
     *
     * @param mixed $needle
     * @param mixed $haystack
     * @param mixed $message
     */
    public static function assertStringContainsString($needle, $haystack, $message = '')
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::assertStringContainsString($needle, $haystack, $message);

            return;
        }

        self::assertContains($needle, $haystack, $message);
    }

    /**
     * Backwards compatible assertions (as far as in use)
     *
     * @param string $name
     * @param array $arguments
     */
    public function __call($name, array $arguments)
    {
        switch ($name) {
            // file and directory assertions came w/ PHPUnit 5.7
            case 'assertDirectoryExists':
                list($directory, $message) = $arguments + array(1 => '');
                $this->shimAssertDirectoryExists($directory, $message);

                return;
            case 'assertDirectoryNotExists':
                list($directory, $message) = $arguments + array(1 => '');
                $this->shimAssertDirectoryNotExists($directory, $message);

                return;
            case 'assertFileExists':
                list($filename, $message) = $arguments + array(1 => '');
                $this->shimAssertFileExists($filename, $message);

                return;
            case 'assertFileNotExists':
                list($filename, $message) = $arguments + array(1 => '');
                $this->shimAssertFileNotExists($filename, $message);

                return;
        }

        throw new \BadMethodCallException(
            sprintf('Testcase %s::%s(%d)', get_class($this), $name, count($arguments))
        );
    }

    /* exceptions */

    protected function setUp()
    {
        $this->expectedException = null;
        $this->expectedExceptionMessage = null;
    }

    /**
     * @param string $exception
     */
    public function expectException($exception)
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::expectException($exception);

            return;
        }

        $this->expectedException = $exception;
        $this->setExpectedException($exception);
    }

    /**
     * @param string $message
     *
     * @throws Exception
     */
    public function expectExceptionMessage($message)
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::expectExceptionMessage($message);

            return;
        }

        if (null === $this->expectedException) {
            throw new \BadMethodCallException('Hmm this is message without class *gg* - reflection?');
        }

        $this->expectedExceptionMessage = $message;
        $this->setExpectedException($this->expectedException, $message);
    }

    /**
     * @param int|string $code
     *
     * @throws Exception
     */
    public function expectExceptionCode($code)
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::expectExceptionCode($code);

            return;
        }

        $this->setExpectedException($this->expectedException, $this->expectedExceptionMessage, $code);
    }

    /**
     * Phpunit 8 deprecated (first silently) expectExceptionMessageRegExp(),
     * will be gone in Phpunit 9.
     *
     * @param string $messageRegExp
     *
     * @throws Exception
     */
    public function expectExceptionMessageMatches($messageRegExp)
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::expectExceptionMessageMatches($messageRegExp);

            return;
        }

        $this->expectExceptionMessageRegExp($messageRegExp);
    }

    public function expectExceptionMessageRegExp($messageRegExp)
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::expectExceptionMessageRegExp($messageRegExp);

            return;
        }

        if (null === $this->expectedException) {
            throw new \BadMethodCallException('Hmm this is message-regex without class *gg* - reflection?');
        }

        $this->setExpectedExceptionRegExp($this->expectedException, $messageRegExp);
    }

    public function setExpectedException($class, $message = null, $code = null)
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::setExpectedException($class, $message, $code);

            return;
        }

        if (is_callable('parent::expectException')) {
            $this->expectException($class);
        }

        if (null !== $message && is_callable('parent::expectExceptionMessage')) {
            $this->expectExceptionMessage($message);
        }

        if (null !== $code && is_callable('parent::expectExceptionCode')) {
            $this->expectExceptionCode($code);
        }
    }

    public function setExpectedExceptionRegExp($class, $messageRegExp = '', $code = null)
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::setExpectedExceptionRegExp($class, $messageRegExp, $code);

            return;
        }

        if (is_callable('parent::expectException')) {
            $this->expectException($class);
        }

        if (is_callable('parent::expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches($messageRegExp);
        }

        if (null !== $code && is_callable('parent::expectExceptionCode')) {
            $this->expectExceptionCode($code);
        }
    }

    /* mocks */

    /**
     * Returns a test double for the specified class.
     *
     * @param string $originalClassName
     *
     * @throws Exception
     *
     * @return MockObject
     *
     */
    protected function createMock($originalClassName)
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            return parent::createMock($originalClassName);
        }

        return $this->getMockBuilder($originalClassName)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            # phpunit ^4 does not have the method:
            # ->disallowMockingUnknownTypes()
            ->getMock();
    }

    /**
     * Returns a configured test double for the specified class.
     *
     * @param string $originalClassName
     * @param array  $configuration
     *
     * @throws Exception
     *
     * @return MockObject
     *
     */
    protected function createConfiguredMock($originalClassName, array $configuration)
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            return parent::createConfiguredMock($originalClassName, $configuration);
        }

        $o = $this->createMock($originalClassName);

        foreach ($configuration as $method => $return) {
            $o->method($method)->willReturn($return);
        }

        return $o;
    }

    /**
     * Returns a partial test double for the specified class.
     *
     * @param string   $originalClassName
     * @param string[] $methods
     *
     * @throws
     *
     * @return MockObject
     *
     */
    protected function createPartialMock($originalClassName, array $methods)
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            return parent::createPartialMock($originalClassName, $methods);
        }

        return $this->getMockBuilder($originalClassName)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            # phpunit ^4 does not have the method:
            # ->disallowMockingUnknownTypes()
            ->setMethods(empty($methods) ? null : $methods)
            ->getMock();
    }

    /**
     * Asserts that a directory exists.
     *
     * @param string $directory
     * @param string $message
     */
    private function shimAssertDirectoryExists($directory, $message = '')
    {
        if (!\is_string($directory)) {
            throw InvalidArgumentHelper::factory(1, 'string');
        }

        /** @noinspection PhpUnitTestsInspection */
        $this->assertTrue(\is_dir($directory), $message);
    }

    /**
     * Asserts that a directory exists.
     *
     * @param string $directory
     * @param string $message
     */
    private function shimAssertDirectoryNotExists($directory, $message = '')
    {
        if (!\is_string($directory)) {
            throw InvalidArgumentHelper::factory(1, 'string');
        }

        /** @noinspection PhpUnitTestsInspection */
        $this->assertFalse(\is_dir($directory), $message);
    }

    /**
     * Asserts that a file exists.
     *
     * @param string $filename
     * @param string $message
     */
    private function shimAssertFileExists($filename, $message = '')
    {
        if (!\is_string($filename)) {
            throw InvalidArgumentHelper::factory(1, 'string');
        }

        /** @noinspection PhpUnitTestsInspection */
        $this->assertTrue(\file_exists($filename), $message);
    }

    /**
     * Asserts that a file exists.
     *
     * @param string $filename
     * @param string $message
     */
    private function shimAssertFileNotExists($filename, $message = '')
    {
        if (!\is_string($filename)) {
            throw InvalidArgumentHelper::factory(1, 'string');
        }

        /** @noinspection PhpUnitTestsInspection */
        $this->assertFalse(\file_exists($filename), $message);
    }
}
