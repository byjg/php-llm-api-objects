<?php

namespace Tests\Tool;

use ByJG\LlmApiObjects\Tool\ToolParameter;
use PHPUnit\Framework\TestCase;

class ToolParameterTest extends TestCase
{
    public function testMinimalSchema(): void
    {
        $param = new ToolParameter(name: 'location', type: 'string');
        $schema = $param->toSchema();

        $this->assertSame(['type' => 'string'], $schema);
        $this->assertArrayNotHasKey('description', $schema);
        $this->assertArrayNotHasKey('enum', $schema);
    }

    public function testSchemaWithDescription(): void
    {
        $param = new ToolParameter(name: 'location', type: 'string', description: 'City name');
        $schema = $param->toSchema();

        $this->assertSame('string', $schema['type']);
        $this->assertSame('City name', $schema['description']);
        $this->assertArrayNotHasKey('enum', $schema);
    }

    public function testSchemaWithEnum(): void
    {
        $param = new ToolParameter(name: 'unit', type: 'string', enum: ['celsius', 'fahrenheit']);
        $schema = $param->toSchema();

        $this->assertSame('string', $schema['type']);
        $this->assertSame(['celsius', 'fahrenheit'], $schema['enum']);
    }

    public function testSchemaWithDescriptionAndEnum(): void
    {
        $param = new ToolParameter(
            name: 'unit',
            type: 'string',
            description: 'Temperature unit',
            enum: ['celsius', 'fahrenheit'],
        );
        $schema = $param->toSchema();

        $this->assertSame('string', $schema['type']);
        $this->assertSame('Temperature unit', $schema['description']);
        $this->assertSame(['celsius', 'fahrenheit'], $schema['enum']);
    }

    public function testRequiredFlagDoesNotAffectSchema(): void
    {
        // required is handled by ToolFunction, not the schema itself
        $param = new ToolParameter(name: 'x', type: 'integer', required: true);
        $schema = $param->toSchema();

        $this->assertSame(['type' => 'integer'], $schema);
    }

    public function testNumericTypes(): void
    {
        foreach (['number', 'integer', 'boolean', 'array', 'object'] as $type) {
            $param = new ToolParameter(name: 'val', type: $type);
            $this->assertSame($type, $param->toSchema()['type']);
        }
    }
}
