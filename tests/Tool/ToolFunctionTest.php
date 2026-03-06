<?php

namespace ByJG\LlmApiObjects\Tests\Tool;

use ByJG\LlmApiObjects\Tool\ToolFunction;
use ByJG\LlmApiObjects\Tool\ToolParameter;
use PHPUnit\Framework\TestCase;

class ToolFunctionTest extends TestCase
{
    public function testMinimalFunction(): void
    {
        $fn = new ToolFunction(name: 'get_time');
        $api = $fn->toApi();

        $this->assertSame('get_time', $api['name']);
        $this->assertArrayNotHasKey('description', $api);
        $this->assertSame('object', $api['parameters']['type']);
        $this->assertSame([], $api['parameters']['properties']);
        $this->assertArrayNotHasKey('required', $api['parameters']);
    }

    public function testFunctionWithDescription(): void
    {
        $fn = new ToolFunction(name: 'get_weather', description: 'Returns the weather for a location');
        $api = $fn->toApi();

        $this->assertSame('Returns the weather for a location', $api['description']);
    }

    public function testFunctionWithOptionalParameter(): void
    {
        $fn = new ToolFunction(
            name: 'get_weather',
            parameters: [
                new ToolParameter(name: 'location', type: 'string', description: 'City name', required: false),
            ],
        );
        $api = $fn->toApi();

        $this->assertArrayHasKey('location', $api['parameters']['properties']);
        $this->assertSame('string', $api['parameters']['properties']['location']['type']);
        $this->assertArrayNotHasKey('required', $api['parameters']);
    }

    public function testFunctionWithRequiredParameter(): void
    {
        $fn = new ToolFunction(
            name: 'get_weather',
            parameters: [
                new ToolParameter(name: 'location', type: 'string', required: true),
            ],
        );
        $api = $fn->toApi();

        $this->assertContains('location', $api['parameters']['required']);
    }

    public function testFunctionMixedRequiredOptional(): void
    {
        $fn = new ToolFunction(
            name: 'search',
            parameters: [
                new ToolParameter(name: 'query', type: 'string', required: true),
                new ToolParameter(name: 'limit', type: 'integer', required: false),
                new ToolParameter(name: 'offset', type: 'integer', required: true),
            ],
        );
        $api = $fn->toApi();

        $this->assertContains('query', $api['parameters']['required']);
        $this->assertContains('offset', $api['parameters']['required']);
        $this->assertNotContains('limit', $api['parameters']['required']);

        $this->assertArrayHasKey('query', $api['parameters']['properties']);
        $this->assertArrayHasKey('limit', $api['parameters']['properties']);
        $this->assertArrayHasKey('offset', $api['parameters']['properties']);
    }

    public function testOpenAiWeatherToolShape(): void
    {
        // Matches the exact structure from OpenAI docs / model spec
        $fn = new ToolFunction(
            name: 'get_current_weather',
            description: 'Get the current weather in a given location',
            parameters: [
                new ToolParameter(name: 'location', type: 'string', description: 'The city and state, e.g. San Francisco, CA', required: true),
                new ToolParameter(name: 'unit', type: 'string', enum: ['celsius', 'fahrenheit'], required: false),
            ],
        );
        $api = $fn->toApi();

        $expected = [
            'name' => 'get_current_weather',
            'description' => 'Get the current weather in a given location',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'location' => [
                        'type' => 'string',
                        'description' => 'The city and state, e.g. San Francisco, CA',
                    ],
                    'unit' => [
                        'type' => 'string',
                        'enum' => ['celsius', 'fahrenheit'],
                    ],
                ],
                'required' => ['location'],
            ],
        ];

        $this->assertSame($expected, $api);
    }
}
