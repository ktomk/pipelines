<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use PHPUnit\Framework\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class UnitTestCase extends TestCase
{
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
        // shim for older phpunit versions

        if (is_callable('parent::' . __METHOD__)) {
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
}
