<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Opts;

use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Runner\Opts\User
 */
class UserTest extends TestCase
{
    public function testCreation()
    {
        $user = new User('0');
        self::assertNotNull($user);
    }

    public function testUserToString()
    {
        $user = new User('0');
        self::assertSame('0', $user->toString());
    }

    public function testUserToUidGidOptional()
    {
        $user = new User('0');
        $actual = $user->toUidGidArray();
        self::assertSame(array(0, null), $actual);
    }

    public function testUserToUidGid()
    {
        $user = new User('0:0');
        $actual = $user->toUidGidArray();
        self::assertSame(array(0, 0), $actual);
    }

    public function testUserToUidLetsNotPassMinusInFront()
    {
        $user = new User('-0');
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('illegal username: -0');
        $user->toUidGidArray();
    }

    public function testUserToUidGidThrowsInUsernameBecauseNoMaps()
    {
        $user = new User('uname');
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('unable to find user uname: there is no owner and group map. use numeric user/group ids instead.');
        $user->toUidGidArray();
    }
}
