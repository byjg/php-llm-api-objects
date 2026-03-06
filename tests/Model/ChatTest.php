<?php

namespace ByJG\LlmApiObjects\Tests\Model;

use ByJG\LlmApiObjects\Enum\Role;
use ByJG\LlmApiObjects\Enum\ToolChoice;
use ByJG\LlmApiObjects\Model\Chat;
use ByJG\LlmApiObjects\Model\Message;
use ByJG\LlmApiObjects\Tool\Tool;
use ByJG\LlmApiObjects\Tool\ToolFunction;
use ByJG\LlmApiObjects\Tool\ToolParameter;
use PHPUnit\Framework\TestCase;

class ChatTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Basic structure
    // -------------------------------------------------------------------------

    public function testBasicChatToApiShape(): void
    {
        $chat = new Chat(model: 'gpt-4o', messages: [
            new Message(role: Role::user, message: 'Hello'),
        ]);
        $api = $chat->toApi();

        // OpenAI Chat Completions required keys
        $this->assertArrayHasKey('model', $api);
        $this->assertArrayHasKey('messages', $api);
        $this->assertSame('gpt-4o', $api['model']);
        $this->assertCount(1, $api['messages']);
    }

    public function testSystemMessagePrependedFromSystemProperty(): void
    {
        $chat = new Chat(
            model: 'gpt-4o',
            messages: [new Message(role: Role::user, message: 'Hi')],
            system: 'You are a helpful assistant.',
        );
        $api = $chat->toApi();

        $this->assertCount(2, $api['messages']);
        $this->assertSame('system', $api['messages'][0]['role']);
        $this->assertSame('You are a helpful assistant.', $api['messages'][0]['content']);
        $this->assertSame('user', $api['messages'][1]['role']);
    }

    public function testMessageRolesAreStrings(): void
    {
        $chat = new Chat(model: 'gpt-4o', messages: [
            new Message(role: Role::user, message: 'Hello'),
            new Message(role: Role::assistant, message: 'Hi there'),
        ]);
        $api = $chat->toApi();

        foreach ($api['messages'] as $msg) {
            $this->assertIsString($msg['role']);
            $this->assertIsString($msg['content']);
        }
    }

    // -------------------------------------------------------------------------
    // Tools
    // -------------------------------------------------------------------------

    public function testNoToolsKeyWhenEmpty(): void
    {
        $chat = new Chat(model: 'gpt-4o', messages: [
            new Message(role: Role::user, message: 'Hello'),
        ]);
        $api = $chat->toApi();

        $this->assertArrayNotHasKey('tools', $api);
        $this->assertArrayNotHasKey('tool_choice', $api);
    }

    public function testSingleToolAppearsInApi(): void
    {
        $chat = new Chat(model: 'gpt-4o', messages: [
            new Message(role: Role::user, message: 'What is the weather?'),
        ]);
        $chat->addTool(new Tool(new ToolFunction(
            name: 'get_weather',
            description: 'Get weather',
            parameters: [
                new ToolParameter(name: 'location', type: 'string', required: true),
            ],
        )));
        $api = $chat->toApi();

        $this->assertArrayHasKey('tools', $api);
        $this->assertCount(1, $api['tools']);
        $this->assertSame('function', $api['tools'][0]['type']);
        $this->assertSame('get_weather', $api['tools'][0]['function']['name']);
    }

    public function testMultipleToolsInApi(): void
    {
        $chat = new Chat(model: 'gpt-4o', messages: [
            new Message(role: Role::user, message: 'Hello'),
        ]);
        $chat->addTool(new Tool(new ToolFunction(name: 'tool_a')));
        $chat->addTool(new Tool(new ToolFunction(name: 'tool_b')));
        $api = $chat->toApi();

        $this->assertCount(2, $api['tools']);
        $this->assertSame('tool_a', $api['tools'][0]['function']['name']);
        $this->assertSame('tool_b', $api['tools'][1]['function']['name']);
    }

    // -------------------------------------------------------------------------
    // tool_choice
    // -------------------------------------------------------------------------

    public function testToolChoiceAutoValue(): void
    {
        $chat = new Chat(model: 'gpt-4o', messages: [
            new Message(role: Role::user, message: 'Hello'),
        ]);
        $chat->addTool(new Tool(new ToolFunction(name: 'my_fn')));
        $chat->setToolChoice(ToolChoice::auto);
        $api = $chat->toApi();

        $this->assertSame('auto', $api['tool_choice']);
    }

    public function testToolChoiceNoneValue(): void
    {
        $chat = new Chat(model: 'gpt-4o', messages: [
            new Message(role: Role::user, message: 'Hello'),
        ]);
        $chat->addTool(new Tool(new ToolFunction(name: 'my_fn')));
        $chat->setToolChoice(ToolChoice::none);
        $api = $chat->toApi();

        $this->assertSame('none', $api['tool_choice']);
    }

    public function testToolChoiceRequiredValue(): void
    {
        $chat = new Chat(model: 'gpt-4o', messages: [
            new Message(role: Role::user, message: 'Hello'),
        ]);
        $chat->addTool(new Tool(new ToolFunction(name: 'my_fn')));
        $chat->setToolChoice(ToolChoice::required);
        $api = $chat->toApi();

        $this->assertSame('required', $api['tool_choice']);
    }

    public function testToolChoiceSpecificFunction(): void
    {
        $tool = new Tool(new ToolFunction(name: 'get_weather'));
        $chat = new Chat(model: 'gpt-4o', messages: [
            new Message(role: Role::user, message: 'Hello'),
        ]);
        $chat->addTool($tool);
        $chat->setToolChoice($tool->asChoice());
        $api = $chat->toApi();

        // OpenAI spec: {"type": "function", "function": {"name": "get_weather"}}
        $this->assertIsArray($api['tool_choice']);
        $this->assertSame('function', $api['tool_choice']['type']);
        $this->assertSame('get_weather', $api['tool_choice']['function']['name']);
    }

    // -------------------------------------------------------------------------
    // Tool result turn (role=tool)
    // -------------------------------------------------------------------------

    public function testDeveloperRoleInMessages(): void
    {
        $chat = new Chat(model: 'gpt-4o', messages: [
            new Message(role: Role::developer, message: 'Always respond in JSON.'),
            new Message(role: Role::user, message: 'Hello'),
        ]);
        $api = $chat->toApi();

        $this->assertSame('developer', $api['messages'][0]['role']);
        $this->assertSame('user', $api['messages'][1]['role']);
    }

    public function testFullMultiTurnWithToolCalls(): void
    {
        // Turn 1: user asks
        // Turn 2: assistant responds with tool_calls (null content)
        // Turn 3: tool returns result
        // This is the exact shape OpenAI expects for multi-turn tool conversations
        $toolCalls = [
            ['id' => 'call_abc123', 'type' => 'function', 'function' => ['name' => 'get_weather', 'arguments' => '{"location":"Paris"}']],
        ];

        $chat = new Chat(model: 'gpt-4o', messages: [
            new Message(role: Role::user, message: 'What is the weather in Paris?'),
            new Message(role: Role::assistant, message: null, toolCalls: $toolCalls),
            new Message(role: Role::tool, message: '{"temperature": 22, "unit": "celsius"}', toolCallId: 'call_abc123'),
        ]);
        $api = $chat->toApi();

        $this->assertCount(3, $api['messages']);

        // User turn
        $this->assertSame('user', $api['messages'][0]['role']);

        // Assistant turn: null content + tool_calls
        $this->assertSame('assistant', $api['messages'][1]['role']);
        $this->assertNull($api['messages'][1]['content']);
        $this->assertSame($toolCalls, $api['messages'][1]['tool_calls']);

        // Tool result turn
        $this->assertSame('tool', $api['messages'][2]['role']);
        $this->assertSame('call_abc123', $api['messages'][2]['tool_call_id']);
    }

    public function testToolResultTurnShape(): void
    {
        // Simulates the multi-turn: user -> assistant -> tool result
        $chat = new Chat(model: 'gpt-4o', messages: [
            new Message(role: Role::user, message: 'What is the weather in Paris?'),
            new Message(role: Role::assistant, message: 'I will check the weather for you.'),
            new Message(role: Role::tool, message: '{"temperature": 22, "unit": "celsius"}', toolCallId: 'call_abc123'),
        ]);
        $api = $chat->toApi();

        $this->assertCount(3, $api['messages']);
        $toolMsg = $api['messages'][2];
        $this->assertSame('tool', $toolMsg['role']);
        $this->assertSame('call_abc123', $toolMsg['tool_call_id']);
        $this->assertSame('{"temperature": 22, "unit": "celsius"}', $toolMsg['content']);
    }

    // -------------------------------------------------------------------------
    // Full round-trip: OpenAI weather tool example
    // -------------------------------------------------------------------------

    public function testFullWeatherToolRequestShape(): void
    {
        $weatherTool = new Tool(
            new ToolFunction(
                name: 'get_current_weather',
                description: 'Get the current weather in a given location',
                parameters: [
                    new ToolParameter(
                        name: 'location',
                        type: 'string',
                        description: 'The city and state, e.g. San Francisco, CA',
                        required: true,
                    ),
                    new ToolParameter(
                        name: 'unit',
                        type: 'string',
                        enum: ['celsius', 'fahrenheit'],
                    ),
                ],
            ),
        );

        $chat = new Chat(
            model: 'gpt-4o',
            messages: [
                new Message(role: Role::user, message: "What's the weather like in Boston?"),
            ],
        );
        $chat->addTool($weatherTool);
        $chat->setToolChoice(ToolChoice::auto);

        $api = $chat->toApi();

        // Validate top-level structure
        $this->assertSame('gpt-4o', $api['model']);
        $this->assertSame('auto', $api['tool_choice']);
        $this->assertCount(1, $api['messages']);
        $this->assertCount(1, $api['tools']);

        // Validate tool shape matches OpenAI spec
        $tool = $api['tools'][0];
        $this->assertSame('function', $tool['type']);
        $this->assertSame('get_current_weather', $tool['function']['name']);
        $this->assertSame('object', $tool['function']['parameters']['type']);
        $this->assertArrayHasKey('location', $tool['function']['parameters']['properties']);
        $this->assertArrayHasKey('unit', $tool['function']['parameters']['properties']);
        $this->assertSame(['location'], $tool['function']['parameters']['required']);
        $this->assertSame(['celsius', 'fahrenheit'], $tool['function']['parameters']['properties']['unit']['enum']);
    }
}
