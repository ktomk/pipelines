<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use BadMethodCallException;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as PhpunitTestCase;

/**
 * UnitTestCase
 *
 * Base test case for pipelines test-suite, basically a shim
 * for older/newer phpunit versions.
 *
 * @method static assertDirectoryNotExists(string $directory, string $message = '') - deprecated in phpunit 9
 * @method static assertDirectoryNotExist(string $directory, string $message = '')
 * @method static assertFileNotExists(string $filename, string $message = '') - deprecated in phpunit 9
 * @method static assertFileNotExist(string $filename, string $message = '')
 * @method static assertRegExp(string $pattern, string $string, string $message = '') - deprecated in phpunit 9
 * {@see TestCase::assertMatchesRegularExpression}
 *
 * Type hinting for PHP 5.3 language level in PhpStorm w/ Phpunit 9.5 on PHP 7.4
 * @method static assertInstanceOf(string $expected, $actual, string $message = '')
 * @method expectOutputString(string $expectedString)
 * @method expectOutputRegex(string $expectedRegex)
 * @method getMockForAbstractClass(string $originalClassName)
 *
 * @see TestCase::assertMatchesRegularExpression
 *
 * @coversNothing
 */
class TestCase extends PhpunitTestCase
{
    /**
     * @var null|string
     */
    private $expectedException;

    /**
     * @var null|string
     */
    private $expectedExceptionMessage;

    /**
     * @param $actual
     * @param string $message
     */
    public static function assertIsArray($actual, $message = ''): void
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::assertIsArray($actual, $message);

            return;
        }

        self::assertInternalType('array', $actual, $message);
    }

    public static function assertIsBool($actual, $message = ''): void
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::assertIsBool($actual, $message);

            return;
        }

        self::assertInternalType('bool', $actual, $message);
    }

    public static function assertIsCallable($actual, $message = ''): void
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::assertIsCallable($actual, $message);

            return;
        }

        self::assertInternalType('callable', $actual, $message);
    }

    public static function assertIsInt($actual, $message = ''): void
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::assertIsInt($actual, $message);

            return;
        }

        self::assertInternalType('integer', $actual, $message);
    }

    public static function assertIsString($actual, $message = ''): void
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::assertIsString($actual, $message);

            return;
        }

        self::assertInternalType('string', $actual, $message);
    }

    /**
     * Asserts that a string matches a given regular expression.
     *
     * @param string $pattern
     * @param string $string
     * @param string $message
     */
    public static function assertMatchesRegularExpression($pattern, $string, $message = ''): void
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::assertMatchesRegularExpression($pattern, $string, $message);

            return;
        }

        self::assertRegExp($pattern, $string, $message);
    }

    /**
     * assertContains() with string haystacks >>> assertStringContainsString
     *
     * assertStringContainsString was added in Phpunit 8 to lighten up usage
     * of assertContains power-factory
     *
     * @param string $needle
     * @param string $haystack
     * @param string $message
     */
    public static function assertStringContainsString($needle, $haystack, $message = ''): void
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::assertStringContainsString($needle, $haystack, $message);

            return;
        }

        self::assertContains($needle, $haystack, $message);
    }

    /**
     * assertContains() with string haystacks >>> assertStringContainsString
     *
     * assertStringContainsString was added in Phpunit 8 to lighten up usage
     * of assertContains power-factory
     *
     * @param string $needle
     * @param string $haystack
     * @param string $message
     *
     * @return void
     */
    public static function assertStringNotContainsString($needle, $haystack, $message = ''): void
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::assertStringNotContainsString($needle, $haystack, $message);

            return;
        }

        self::assertNotContains($needle, $haystack, $message);
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
            case 'assertDirectoryNotExist':
                list($directory, $message) = $arguments + array(1 => '');
                $this->shimAssertDirectoryNotExists($directory, $message);

                return;
            case 'assertFileExists':
                list($filename, $message) = $arguments + array(1 => '');
                $this->shimAssertFileExists($filename, $message);

                return;
            case 'assertFileNotExists':
            case 'assertFileNotExist':
                list($filename, $message) = $arguments + array(1 => '');
                $this->shimAssertFileNotExists($filename, $message);

                return;
        }

        throw new \BadMethodCallException(
            sprintf('Testcase %s::%s(%d)', get_class($this), $name, count($arguments))
        );
    }

    /* setUp / tearDown phpunit compat overrides */

    /**
     * @deprecated phpunit compat shim, use doSetUp() downstream
     * @see doSetUp
     */
    protected function setUp(): void
    {
        $this->doSetUp();
    }

    /**
     * phpunit compat shim
     *
     * @see setUp
     */
    protected function doSetUp()
    {
        $this->expectedException = null;
        $this->expectedExceptionMessage = null;
    }

    /**
     * @deprecated phpunit compat shim, use doTearDown() downstream
     * @see doTearDown
     */
    protected function tearDown(): void
    {
        $this->doTearDown();
    }

    /**
     * phpunit compat shim
     *
     * @see tearDown
     */
    protected function doTearDown()
    {
        parent::tearDown();
    }

    /* exceptions */

    /**
     * @param string $exception
     *
     * @return void
     */
    public function expectException($exception): void
    {
        $this->expectedException = $exception;

        if (is_callable('parent::' . __FUNCTION__)) {
            parent::expectException($exception);

            return;
        }

        $this->setExpectedException($exception);
    }

    /**
     * @param string $message
     *
     * @throws Exception
     *
     * @return void
     */
    public function expectExceptionMessage($message): void
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
     *
     * @return void
     */
    public function expectExceptionCode($code): void
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::expectExceptionCode($code);

            return;
        }

        if (null === $this->expectedException) {
            throw new BadMethodCallException('No exception expected');
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
     *
     * @return void
     */
    public function expectExceptionMessageMatches($messageRegExp): void
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            parent::expectExceptionMessageMatches($messageRegExp);

            return;
        }

        $this->expectExceptionMessageRegExp($messageRegExp);
    }

    /**
     * @param string $messageRegExp
     *
     * @deprecated In phpunit 8. Use expectExceptionMessageMatches() instead
     * @see expectExceptionMessageMatches
     *
     * @return void
     */
    public function expectExceptionMessageRegExp($messageRegExp): void
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

    /**
     * @param string $class
     * @param null|string $message
     * @param null|int|string $code
     *
     * @return void
     */
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

    /**
     * @param string $class
     * @param string $messageRegExp
     * @param null|int|string $code
     *
     * @return void
     */
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
     * @throws \InvalidArgumentException
     *
     * @return MockObject
     */
    protected function createMock($originalClassName): MockObject
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
     * @param array $configuration
     *
     * @throws Exception
     * @throws \InvalidArgumentException
     *
     * @return MockObject
     */
    protected function createConfiguredMock($originalClassName, array $configuration): MockObject
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
     * @param string $originalClassName
     * @param string[] $methods
     *
     * @throws Exception
     * @throws \InvalidArgumentException
     *
     * @return MockObject
     */
    protected function createPartialMock($originalClassName, array $methods): MockObject
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
     *
     * @return void
     */
    private function shimAssertDirectoryExists($directory, $message = '')
    {
        if (!\is_string($directory)) {
            throw new \InvalidArgumentException('$directory is not a string');
        }

        /** @noinspection PhpUnitTestsInspection */
        self::assertTrue(\is_dir($directory), $message);
    }

    /**
     * Asserts that a directory exists.
     *
     * @param string $directory
     * @param string $message
     *
     * @return void
     */
    private function shimAssertDirectoryNotExists($directory, $message = '')
    {
        if (!\is_string($directory)) {
            throw new \InvalidArgumentException('$directory is not a string');
        }

        /** @noinspection PhpUnitTestsInspection */
        self::assertFalse(\is_dir($directory), $message);
    }

    /**
     * Asserts that a file exists.
     *
     * @param string $filename
     * @param string $message
     *
     * @return void
     */
    private function shimAssertFileExists($filename, $message = '')
    {
        if (!\is_string($filename)) {
            throw new \InvalidArgumentException('$filename is not a string');
        }

        /** @noinspection PhpUnitTestsInspection */
        self::assertTrue(\file_exists($filename), $message);
    }

    /**
     * Asserts that a file exists.
     *
     * @param string $filename
     * @param string $message
     *
     * @return void
     */
    private function shimAssertFileNotExists($filename, $message = '')
    {
        if (!\is_string($filename)) {
            throw new \InvalidArgumentException('$filename is not a string');
        }

        /** @noinspection PhpUnitTestsInspection */
        self::assertFalse(\file_exists($filename), $message);
    }
}
