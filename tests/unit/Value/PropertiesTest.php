<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Value;

use Ktomk\Pipelines\TestCase;

/**
 * @covers \Ktomk\Pipelines\Value\Properties
 */
class PropertiesTest extends TestCase
{
    public function testCreation()
    {
        $properties = new Properties();
        self::assertInstanceOf(
            'Ktomk\Pipelines\Value\Properties',
            $properties
        );

        return $properties;
    }

    /**
     * @depends testCreation
     *
     * @param Properties $properties
     */
    public function testCountableOnCreation(Properties $properties)
    {
        self::assertCount(0, $properties);
    }

    /**
     * @depends testCreation
     *
     * @param Properties $properties
     */
    public function testHasOnCreation(Properties $properties)
    {
        self::assertFalse($properties->has('baz'));
    }

    /**
     * @depends testCreation
     *
     * @param Properties $properties
     */
    public function testToArrayOnCreation(Properties $properties)
    {
        self::assertSame(array(), $properties->toArray());
    }

    /**
     * @param Properties $properties
     * @depends testCreation
     */
    public function testExportOnCreation(Properties $properties)
    {
        self::assertSame(array(), $properties->export(array()));
    }

    public function testImport()
    {
        $properties = new Properties();
        $array = array(
            'foo' => 'le foo',
            'baz' => 'le baz',
        );
        $actual = $properties->import($array, array('foo', 'bar'));
        $expected = array(
            'baz' => 'le baz',
        );
        self::assertSame($expected, $actual);

        return $properties;
    }

    /**
     * @param Properties $properties
     * @depends testImport
     */
    public function testHasAfterImport(Properties $properties)
    {
        self::assertTrue($properties->has('foo'));
    }

    /**
     * @param Properties $properties
     * @depends testImport
     */
    public function testToArrayAfterImport(Properties $properties)
    {
        $actual = $properties->toArray();
        $expected = array(
            'foo' => 'le foo',
        );
        self::assertSame($expected, $actual);
    }

    /**
     * @param Properties $properties
     * @depends testImport
     */
    public function testExportAfterImport(Properties $properties)
    {
        $actual = $properties->export(array('foo', 'bar', 'baz'));
        $expected = array(
            'foo' => 'le foo',
        );
        self::assertSame($expected, $actual);
    }

    public function testExportProperties()
    {
        $properties = new Properties();
        $array = array('bar' => 'le bar');
        $properties->importPropertiesArray($array);

        $actual = $properties->exportPropertiesByName(array('bar', 'baz'));
        self::assertSame($array, $actual);
    }

    public function testExportWithRequiredKey()
    {
        $properties = new Properties();
        $array = array('foo' => 'le foo', 'bar' => 'le bar');
        $properties->import($array, array_keys($array));

        $actual = $properties->export(array(array('foo'), array('bar')));
        self::assertSame($array, $actual);
    }

    /**
     */
    public function testExportWithRequiredKeyMissing()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('property/ies "foo" required');

        $properties = new Properties();
        $array = array('bar' => 'le bar');
        $properties->import($array, array_keys($array));

        $properties->export(array(array('foo'), array('baz')));
    }

    /**
     * @param Properties $properties
     * @depends testImport
     */
    public function testCountableAfterImport(Properties $properties)
    {
        self::assertCount(1, $properties);
    }
}
