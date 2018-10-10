<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Args\ArgsTester;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ktomk\Pipelines\Runner\Env
 */
class EnvTest extends TestCase
{
    public function testCreation()
    {
        $env = new Env();
        $this->assertInstanceOf('Ktomk\Pipelines\Runner\Env', $env);
        $this->assertNotNull($env->getArgs('-e'));
    }

    public function testStaticCreation()
    {
        $env = Env::create();
        $this->assertInstanceOf('Ktomk\Pipelines\Runner\Env', $env);
        $this->assertNotNull($env->getArgs('-e'));
    }

    public function testDefaultEnvsEmptyUnlessInitialized()
    {
        $env = new Env();
        $array = $env->getArgs('-e');
        $this->assertInternalType('array', $array);
        $this->assertCount(0, $array);

        $env->initDefaultVars(array());
        $array = $env->getArgs('-e');
        $this->assertCount(10, $array);
    }

    public function testDefaultValue()
    {
        $slug = 'pipelines';
        $env = Env::create(array('BITBUCKET_REPO_SLUG' => $slug));
        $this->assertSame($slug, $env->getValue('BITBUCKET_REPO_SLUG'));
    }

    public function testUserInheritance()
    {
        $user = 'adele';
        $env = Env::create(array('USER' => $user));
        $this->assertNull($env->getValue('USER'));
        $this->assertSame($user, $env->getValue('BITBUCKET_REPO_OWNER'));
    }

    public function testInheritOnInit()
    {
        $env = new Env();
        $env->initDefaultVars(array('BITBUCKET_BUILD_NUMBER' => '123'));
        $array = $env->getArgs('-e');
        $this->assertContains('BITBUCKET_BUILD_NUMBER=123', $array);
    }

    public function testGetOptionArgs()
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

    public function testUnsetVariables()
    {
        $env = new Env();
        $env->initDefaultVars(array());
        # start count has some vars unset
        $default = count($env->getArgs('-e'));

        $env->initDefaultVars(array('BITBUCKET_BRANCH' => 'test'));
        # start count has some vars unset
        $new = count($env->getArgs('-e'));
        $this->assertSame($default + 2, $new);
    }

    public function testAddRefType()
    {
        $env = Env::create();
        $default = count($env->getArgs('-e'));

        $env->addReference(Reference::create());
        $this->assertCount($default, $env->getArgs('-e'), 'null reference does not add any variables');

        $env->addReference(Reference::create('branch:testing'));
        $this->assertCount($default + 2, $env->getArgs('-e'), 'full reference does add variables');
    }

    public function testAddRefTypeIfSet()
    {
        $env = Env::create(array('BITBUCKET_TAG' => 'inherit'));
        $default = count($env->getArgs('-e'));

        $env->addReference(Reference::create('tag:testing'));
        $actual = $env->getArgs('-e');
        $this->assertCount($default, $actual);

        $this->assertContains('BITBUCKET_TAG=inherit', $actual);
    }

    public function testSetContainerName()
    {
        $env = Env::create();
        $count = count($env->getArgs('-e'));

        $env->setContainerName('blue-seldom');
        $args = $env->getArgs('-e');
        $this->assertCount($count + 2, $args);
        $this->assertContains('PIPELINES_CONTAINER_NAME=blue-seldom', $args);

        $env->setContainerName('solar-bottom');
        $args = $env->getArgs('-e');
        $this->assertCount($count + 4, $args);
        $this->assertContains('PIPELINES_PARENT_CONTAINER_NAME=blue-seldom', $args);
        $this->assertContains('PIPELINES_CONTAINER_NAME=solar-bottom', $args);
    }

    public function testInheritedContainerName()
    {
        $inherit = array(
            'PIPELINES_CONTAINER_NAME' => 'cloud-sea',
        );
        $env = Env::create($inherit);
        $env->setContainerName('dream-blue');
        $args = $env->getArgs('-e');
        $this->assertContains('PIPELINES_PARENT_CONTAINER_NAME=cloud-sea', $args);
        $this->assertContains('PIPELINES_CONTAINER_NAME=dream-blue', $args);
    }

    public function testGetVar()
    {
        $env = Env::create();
        $actual = $env->getValue('BITBUCKET_BUILD_NUMBER');
        $this->assertSame('0', $actual);
        $actual = $env->getValue('BITBUCKET_BRANCH');
        $this->assertNull($actual);
    }

    public function testSetPipelinesId()
    {
        $env = Env::create();
        $this->assertNull($env->getValue('PIPELINES_ID'));
        $this->assertNull($env->getValue('PIPELINES_IDS'));

        // set the first id
        $result = $env->setPipelinesId('default');
        $this->assertFalse($result);
        $this->assertSame('default', $env->getValue('PIPELINES_ID'));

        // set the second id (next run)
        $result = $env->setPipelinesId('default');
        $this->assertTrue($result);
        $actual = $env->getValue('PIPELINES_IDS');
        $this->assertNotNull($actual);
        $this->assertRegExp('~^([a-z0-9]+) \1$~', $actual, 'list of hashes');
    }

    public function testInheritPipelinesId()
    {
        $inherit = array('PIPELINES_ID', 'custom/the-other-day');
        $env = Env::create($inherit);
        $this->assertNull($env->getValue('PIPELINES_ID'));
    }

    /**
     * @throws \Ktomk\Pipelines\Cli\ArgsException
     */
    public function testCollect()
    {
        $env = Env::create();
        $env->collect(new ArgsTester(), array());
        $expected = array (
            0 => 'e',
            1 => 'BITBUCKET_BUILD_NUMBER=0',
            2 => 'e',
            3 => 'BITBUCKET_COMMIT=0000000000000000000000000000000000000000',
            4 => 'e',
            5 => 'BITBUCKET_REPO_OWNER=nobody',
            6 => 'e',
            7 => 'BITBUCKET_REPO_SLUG=local-has-no-slug',
            8 => 'e',
            9 => 'CI=true',
        );
        $actual = $env->getArgs('e');
        $this->assertSame($expected, $actual);
    }

    public function testCollectFiles1()
    {
        $env = Env::create(array('DOCKER_ID_USER' => 'electra'));
        $env->collectFiles(array(
            __DIR__ . '/../../data/env/.env.dist',
            '/abc/xyz/nada-kar-la-da',
        ));
        $actual = $env->getResolver()->getValue('DOCKER_ID_USER');
        $this->assertSame('electra', $actual, '.dist imports');

        $actual = $env->getResolver()->getValue('FOO');
        $this->assertSame('BAR', $actual, '.dist sets');
    }

    public function testCollectFiles2()
    {
        $env = Env::create(array('DOCKER_ID_USER' => 'electra'));
        $env->collectFiles(array(
            '/abc/xyz/nada-kar-la-da',
            __DIR__ . '/../../data/env/.env.dist',
            __DIR__ . '/../../data/env/.env',
        ));
        $resolver = $env->getResolver();
        $actual = $resolver->getValue('DOCKER_ID_USER');
        $this->assertSame('l-oracle-de-delphi', $actual, '.env sets');

        $actual = $resolver->getValue('FOO');
        $this->assertSame('BAZ', $actual, '.env overwrites');

        $array = array(
            'first' => '$DOCKER_ID_USER',
            'second'=> '$FOO',
            'third' => '$BAZ-LE-BAZ',
        );
        $expected = array(
            'first' => 'l-oracle-de-delphi',
            'second'=> 'BAZ',
            'third' => '$BAZ-LE-BAZ',
        );
        $actual = $resolver($array);
        $this->assertSame($expected, $actual);
    }

    public function testGetResolver()
    {
        $env = new Env();
        $this->assertInstanceOf(
            'Ktomk\Pipelines\Runner\EnvResolver',
            $env->getResolver()
        );
    }
}
