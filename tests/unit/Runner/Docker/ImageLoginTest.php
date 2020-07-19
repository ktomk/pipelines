<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner\Docker;

use Ktomk\Pipelines\Cli\ExecTester;
use Ktomk\Pipelines\File\Image;
use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Runner\Docker\ImageLogin
 */
class ImageLoginTest extends TestCase
{
    public function testCreation()
    {
        $exec = new ExecTester($this);
        $resolver = function () {
        };
        $login = new ImageLogin($exec, $resolver);
        self::assertInstanceOf('Ktomk\Pipelines\Runner\Docker\ImageLogin', $login);
    }

    public function testByImageWithStringImage()
    {
        $exec = new ExecTester($this);
        $resolver = function () {
        };
        $login = new ImageLogin($exec, $resolver);
        $image = new Image('foo/bar');
        $login->byImage($image);
        $this->addToAssertionCount(1);
    }

    public function testByImageWithImage()
    {
        $exec = new ExecTester($this);
        $exec->expect('capture', 'docker', 0);
        $resolver = function () {
        };
        $path = __DIR__ . '/../../data/docker-config-no-auth.json';
        $login = new ImageLogin($exec, $resolver, $path);
        $array = array(
            'name' => 'account-name/java:8u66',
            'username' => '$DOCKER_HUB_USERNAME',
            'password' => '$DOCKER_HUB_PASSWORD',
            'email' => '$DOCKER_HUB_EMAIL',
        );
        $image = new Image($array);
        $login->byImage($image);
        $this->addToAssertionCount(1);
    }

    public function testDockerLoginHasAuth()
    {
        $exec = new ExecTester($this);
        $resolver = function () {
        };
        $path = __DIR__ . '/../../../data/docker-config.json';
        $login = new ImageLogin($exec, $resolver, $path);
        self::assertTrue($login->dockerLoginHasAuth());
        self::assertTrue($login->dockerLoginHasAuth('existing.foo.host.example:12345'));
        self::assertFalse($login->dockerLoginHasAuth('https://repo.foo/'));
    }

    public function testGetDockerConfigPathFromEnvironment()
    {
        $exec = new ExecTester($this);
        $resolver = function () {
        };
        $login = new ImageLogin($exec, $resolver);
        $actual = $login->getDockerConfigPathFromEnvironment();
        self::assertStringEndsWith('/.docker/config.json', $actual);
    }
}
