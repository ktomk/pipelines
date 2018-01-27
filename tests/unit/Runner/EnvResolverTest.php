<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Runner;

use Ktomk\Pipelines\Cli\Args;
use PHPUnit\Framework\TestCase;

/**
 * Class EnvResolverTest
 *
 * @covers \Ktomk\Pipelines\Runner\EnvResolver
 */
class EnvResolverTest extends TestCase
{
    public function testCreation()
    {
        $resolver = new EnvResolver(array('UID' => '1000'));
        $this->assertInstanceOf('Ktomk\Pipelines\Runner\EnvResolver', $resolver);
    }

    public function testAddArgs()
    {
        $resolver = new EnvResolver(array('UID' => '1000'));
        $args = new Args(array(
            '-env', 'OVERRIDE=red', '-e', 'OVERRIDE=green',
            '--env-file', 'tests/data/test.env', '-e', 'UID',
            '-e', 'MOCHA'
        ));
        $resolver->addArguments($args);
        $this->assertSame('annabelle', $resolver->getValue('USER'));
        $this->assertSame('green', $resolver->getValue('OVERRIDE'));
        $this->assertNull($resolver->getValue('DECAFFEINATED'));
        $this->assertSame('1000', $resolver->getValue('UID'), 'exported variable');
        $this->assertNull($resolver->getValue('MOCHA'), 'unset, file override');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage File read error: '/tmp/xyz/nada-kar-la-da'
     */
    public function testInvalidFile()
    {
        $resolver = new EnvResolver(array('UID' => '1000'));
        @$resolver->addFile('/tmp/xyz/nada-kar-la-da');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Variable definition error: '$'
     */
    public function testInvalidDefinition()
    {
        $resolver = new EnvResolver(array('UID' => '1000'));
        $resolver->addDefinition('$');
    }
}
