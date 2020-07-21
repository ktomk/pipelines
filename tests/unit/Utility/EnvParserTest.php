<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Utility;

use Ktomk\Pipelines\Cli\Args;
use Ktomk\Pipelines\Runner\Reference;
use Ktomk\Pipelines\TestCase;

/**
 * Class EnvParserTest
 *
 * @package Ktomk\Pipelines\Utility
 * @covers \Ktomk\Pipelines\Utility\EnvParser
 */
class EnvParserTest extends TestCase
{
    public function testCreation()
    {
        $args = new Args(array());
        $parser = EnvParser::create($args);
        self::assertInstanceOf('Ktomk\Pipelines\Utility\EnvParser', $parser);
    }

    public function provideParseParameters()
    {
        return array(
            array(array(), null, ''),
        );
    }

    /**
     * @dataProvider provideParseParameters
     *
     * @param array $inherit
     * @param null|string $reference
     * @param string $workingDir
     *
     * @throws \Ktomk\Pipelines\Cli\ArgsException
     */
    public function testParse(array $inherit, $reference, $workingDir)
    {
        $args = new Args(array());

        $reference = new Reference($reference);
        $env = EnvParser::create($args)->parse($inherit, $reference, $workingDir);
        self::assertInstanceOf('Ktomk\Pipelines\Runner\Env', $env);
    }
}
