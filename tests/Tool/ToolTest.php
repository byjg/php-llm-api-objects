<?php

namespace Tests\Tool;

use ByJG\LlmApiObjects\Tool\Tool;
use ByJG\LlmApiObjects\Tool\ToolFunction;
use ByJG\LlmApiObjects\Tool\ToolParameter;
use PHPUnit\Framework\TestCase;

class ToolTest extends TestCase
{
    private function makeWeatherTool(): Tool
    {
        return new Tool(
            new ToolFunction(
                name: 'get_current_weather',
                description: 'Get the current weather in a given location',
                parameters: [
                    new ToolParameter(name: 'location', type: 'string', description: 'City name', required: true),
                    new ToolParameter(name: 'unit', type: 'string', enum: ['celsius', 'fahrenheit']),
                ],
            ),
        );
    }

    public function testToolToApiShape(): void
    {
        $tool = $this->makeWeatherTool();
        $api = $tool->toApi();

        // Top-level must have type and function keys (OpenAI spec)
        $this->assertArrayHasKey('type', $api);
        $this->assertArrayHasKey('function', $api);
        $this->assertSame('function', $api['type']);
    }

    public function testToolToApiFullShape(): void
    {
        $tool = $this->makeWeatherTool();
        $api = $tool->toApi();

        $expected = [
            'type' => 'function',
            'function' => [
                'name' => 'get_current_weather',
                'description' => 'Get the current weather in a given location',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'location' => ['type' => 'string', 'description' => 'City name'],
                        'unit' => ['type' => 'string', 'enum' => ['celsius', 'fahrenheit']],
                    ],
                    'required' => ['location'],
                ],
            ],
        ];

        $this->assertSame($expected, $api);
    }

    public function testAsChoiceShape(): void
    {
        $tool = $this->makeWeatherTool();
        $choice = $tool->asChoice();

        // OpenAI spec: {"type": "function", "function": {"name": "..."}}
        $this->assertSame([
            'type' => 'function',
            'function' => ['name' => 'get_current_weather'],
        ], $choice);
    }

    public function testDefaultTypeIsFunction(): void
    {
        $tool = new Tool(new ToolFunction(name: 'noop'));
        $this->assertSame('function', $tool->type);
        $this->assertSame('function', $tool->toApi()['type']);
    }
}
