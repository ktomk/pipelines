<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Yaml;

use Ktomk\Pipelines\TestCase;

/**
 * Class Sf2YamlTest
 *
 * @covers \Ktomk\Pipelines\Yaml\Sf2Yaml
 *
 * @package Ktomk\Pipelines\Yaml
 */
class Sf2YamlTest extends TestCase
{
    public function testCreation()
    {
        $parser = new Sf2Yaml();
        self::assertInstanceOf('Ktomk\Pipelines\Yaml\Sf2Yaml', $parser);
        self::assertInstanceOf('Ktomk\Pipelines\Yaml\ParserInterface', $parser);

        return $parser;
    }

    /**
     * @param Sf2Yaml $parser
     * @depends testCreation
     */
    public function testParsing(Sf2Yaml $parser)
    {
        $tester = new YamlTester($this, $parser);

        $tester->assertParser();
    }

    /**
     * @depends testCreation
     *
     * @param Sf2Yaml $parser
     * @covers \Ktomk\Pipelines\Yaml\Sf2Yaml::parseFile
     * @covers \Ktomk\Pipelines\Yaml\Yaml::fileDelegate
     */
    public function testParseFile(Sf2Yaml $parser)
    {
        $path = __DIR__ . '/../../../bitbucket-pipelines.yml';

        $struct = $parser->parseFile($path);

        self::assertIsArray($struct);
    }

    /**
     * Symfony YAML based YAML parser needs to return NULL on
     * invalid yaml file which it does not so needs patching
     * tested for.
     *
     * @depends testCreation
     *
     * @param Sf2Yaml $parser
     * @covers \Ktomk\Pipelines\Yaml\Sf2Yaml::parseBuffer
     */
    public function testParseFileError(Sf2Yaml $parser)
    {
        $path = __DIR__ . '/../../data/yml/error.yml';

        $struct = $parser->parseFile($path);

        self::assertNull($struct);
    }
}
