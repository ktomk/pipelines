<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Ktomk\Pipelines\Runner\Env
 */
class EnvTest extends TestCase
{
    function testCreation()
    {
        $env = new Env();
        $this->assertInstanceOf('Ktomk\Pipelines\Runner\Env', $env);
        $this->assertNotNull($env->getArgs('-e'));

        $env = Env::create();
        $this->assertInstanceOf('Ktomk\Pipelines\Runner\Env', $env);
        $this->assertNotNull($env->getArgs('-e'));
    }

    function testDefaultEnvsEmptyUnlessInitialized()
    {
        $env = new Env();
        $array = $env->getArgs('-e');
        $this->assertInternalType('array', $array);
        $this->assertCount(0, $array);

        $env->initDefaultVars(array());
        $array = $env->getArgs('-e');
        $this->assertCount(10, $array);
    }

    function testInheritionOnInit()
    {
        $env = new Env();
        $env->initDefaultVars(array('BITBUCKET_BUILD_NUMBER' => '123'));
        $array = $env->getArgs('-e');
        $this->assertTrue(in_array('BITBUCKET_BUILD_NUMBER=123', $array, true));
    }

    function testGetOptionArgs()
    {
        $env = Env::create();
        $args = $env->getArgs('-e');
        $this->assertInternalType('array', $args);
        while ($args) {
            $argument = array_pop($args);
            $this->assertInternalType('string', $argument);
            $this->assertGreaterThan(0, strpos($argument, '='), 'must be a variable definition');
            $this->assertGreaterThan(0, count($args));
            $option = array_pop($args);
            $this->assertSame('-e', $option);
        }
    }

    function testUnsetVariables()
    {
        $env = new Env();
        $env->initDefaultVars(array());
        # start count has some vars unset
        $default = count($env->getArgs('-e'));

        $env->initDefaultVars(array('BITBUCKET_BRANCH' => 'test'));
        # start count has some vars unset
        $new = count($env->getArgs('-e'));
        $this->assertEquals($default + 2, $new);
    }

    function testAddRefType()
    {
        $env = Env::create();
        $default = count($env->getArgs('-e'));

        $env->addReference(Reference::create());
        $this->assertSame($default, count($env->getArgs('-e')), 'null refrence does not add any variables');

        $env->addReference(Reference::create('branch:testing'));
        $this->assertSame($default + 2, count($env->getArgs('-e')), 'full refrence does add variables');
    }

    function testAddRefTypeIfSet()
    {
        $env = Env::create(array('BITBUCKET_TAG' => 'inherit'));
        $default = count($env->getArgs('-e'));

        $env->addReference(Reference::create('tag:testing'));
        $actual = $env->getArgs('-e');
        $this->assertCount($default, $actual);

        $this->assertTrue(in_array('BITBUCKET_TAG=inherit', $actual, true));
    }
}
