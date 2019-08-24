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
    public function setExpectedException($class, $message = '', $code = null)
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            /** @noinspection PhpUndefinedMethodInspection */
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
            /** @noinspection PhpUndefinedMethodInspection */
            parent::setExpectedExceptionRegExp($class, $messageRegExp, $code);

            return;
        }

        if (is_callable('parent::expectException')) {
            $this->expectException($class);
        }

        if (is_callable('parent::expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp($messageRegExp);
        }

        if (null !== $code && is_callable('parent::expectExceptionCode')) {
            $this->expectExceptionCode($code);
        }
    }

    public static function assertIsArray($actual, $message = '')
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            /** @noinspection PhpUndefinedMethodInspection */
            parent::assertIsArray($actual, $message);

            return;
        }

        self::assertInternalType('array', $actual, $message);
    }

    public static function assertIsBool($actual, $message = '')
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            /** @noinspection PhpUndefinedMethodInspection */
            parent::assertIsBool($actual, $message);

            return;
        }

        self::assertInternalType('bool', $actual, $message);
    }

    public static function assertIsCallable($actual, $message = '')
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            /** @noinspection PhpUndefinedMethodInspection */
            parent::assertIsCallable($actual, $message);

            return;
        }

        self::assertInternalType('callable', $actual, $message);
    }

    public static function assertIsInt($actual, $message = '')
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            /** @noinspection PhpUndefinedMethodInspection */
            parent::assertIsInt($actual, $message);

            return;
        }

        self::assertInternalType('integer', $actual, $message);
    }

    public static function assertIsString($actual, $message = '')
    {
        if (is_callable('parent::' . __FUNCTION__)) {
            /** @noinspection PhpUndefinedMethodInspection */
            parent::assertIsString($actual, $message);

            return;
        }

        self::assertInternalType('string', $actual, $message);
    }

    /**
     * Returns a test double for the specified class.
     *
     * @param string $originalClassName
     *
     * @throws Exception
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
     * Returns a partial test double for the specified class.
     *
     * @param string   $originalClassName
     * @param string[] $methods
     *
     * @throws \Exception
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
