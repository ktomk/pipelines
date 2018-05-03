<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines;

use Ktomk\Pipelines\Yaml\Spyc;
use Ktomk\Pipelines\Yaml\YamlTester;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ktomk\Pipelines\Yaml\Spyc
 */
class SpycTest extends TestCase
{
    public function testCreation()
    {
        $this->assertInstanceOf('Ktomk\Pipelines\Yaml\Spyc', $this->createParser());
    }

    public function testParsing()
    {
        $tester = new YamlTester($this, $this->createParser());

        $tester->assertParser();
    }

    public function testFileParsing()
    {
        $path = __DIR__ . '/../../../bitbucket-pipelines.yml';

        $struct = $this->createParser()->parseFile($path);

        $this->assertInternalType('array', $struct);
    }

    /**
     * Non-YAML files normally parse to empty array for Spyc (sad!)
     */
    public function testNonYamlFile()
    {
        $array = $this->createParser()->parseFile(__FILE__);

        $this->assertNull($array);
    }

    public function testYamlNull()
    {
        $array = $this->createParser()->parseBuffer('first: ~');

        $this->assertArrayHasKey('first', $array);
        $this->assertNull($array['first']);
    }
    private function createParser()
    {
        return new Spyc();
    }
}
