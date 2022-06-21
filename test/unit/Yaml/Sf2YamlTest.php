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
     *
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
     *
     * @covers \Ktomk\Pipelines\Yaml\Sf2Yaml::tryParseFile
     * @covers \Ktomk\Pipelines\Yaml\Yaml::fileDelegate
     */
    public function testParseFile(Sf2Yaml $parser)
    {
        $path = __DIR__ . '/../../../bitbucket-pipelines.yml';

        $struct = $parser->tryParseFile($path);

        self::assertIsArray($struct);

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
     *
     * @covers \Ktomk\Pipelines\Yaml\Sf2Yaml::tryParseBuffer
     */
    public function testParseFileError(Sf2Yaml $parser)
    {
        $path = __DIR__ . '/../../data/yml/yaml/error.yml';

        $struct = $parser->tryParseFile($path);

        self::assertNull($struct);

        $this->expectException(__NAMESPACE__ . '\ParseException');
        $this->expectExceptionMessage('Sf2Yaml invalid YAML parsing');
        $parser->parseFile($path);
    }

    /**
     * Symfony YAML based YAML parser needs to return NULL on
     * invalid yaml file which it does not so needs patching
     * tested for.
     *
     * @depends testCreation
     *
     * @param Sf2Yaml $parser
     *
     * @covers  \Ktomk\Pipelines\Yaml\Sf2Yaml::tryParseBuffer
     */
    public function testParseFileError2(Sf2Yaml $parser)
    {
        $path = __DIR__ . '/../../data/yml/yaml/error-double-quote-string-line-continuation.yml';

        $struct = $parser->tryParseFile($path);

        self::assertNull($struct);

        $this->expectException(__NAMESPACE__ . '\ParseException');
        $this->expectExceptionMessage('Malformed inline YAML string ');
        $parser->parseFile($path);
    }

    /**
     * @depends testCreation
     *
     * @param Sf2Yaml $parser
     *
     * @return void
     */
    public function testParseFileError3(Sf2Yaml $parser)
    {
        $path = __DIR__ . '/../../data/yml/yaml/syntax-issue-16.yml';

        $struct = $parser->tryParseFile($path);

        self::assertNull($struct);

        $this->expectException(__NAMESPACE__ . '\ParseException');
        $this->expectExceptionMessage('Unable to parse at line 3 (near "sleep 10;").');
        $parser->parseFile($path);
    }
}
