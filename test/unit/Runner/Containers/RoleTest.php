<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Containers;

use Ktomk\Pipelines\TestCase;

/**
 * Class RoleTest
 *
 * @package Ktomk\Pipelines\Runner\Containers
 * @covers \Ktomk\Pipelines\Runner\Containers\Role
 */
class RoleTest extends TestCase
{
    public function testVerify()
    {
        Role::verify('step');

        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage('Not a role: "foo"; Roles are: "pipe", "service", "step"');
        Role::verify('foo');
    }
}
