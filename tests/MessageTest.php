<?php

namespace ByJG\LlmApiObjects\Tests;

use ByJG\LlmApiObjects\Enum\Role;
use ByJG\LlmApiObjects\Model\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function testSimpleUserMessage(): void
    {
        $msg = new Message(role: Role::user, message: 'Hello');
        $result = $msg->toApi();

        $this->assertCount(1, $result);
        $this->assertSame('user', $result[0]['role']);
        $this->assertSame('Hello', $result[0]['content']);
        $this->assertArrayNotHasKey('tool_call_id', $result[0]);
    }

    public function testSystemMessage(): void
    {
        $msg = new Message(role: Role::system, message: 'You are a helpful assistant.');
        $result = $msg->toApi();

        $this->assertCount(1, $result);
        $this->assertSame('system', $result[0]['role']);
        $this->assertSame('You are a helpful assistant.', $result[0]['content']);
    }

    public function testAssistantMessage(): void
    {
        $msg = new Message(role: Role::assistant, message: 'I can help with that.');
        $result = $msg->toApi();

        $this->assertSame('assistant', $result[0]['role']);
        $this->assertSame('I can help with that.', $result[0]['content']);
    }

    public function testDeveloperMessage(): void
    {
        $msg = new Message(role: Role::developer, message: 'Always respond in JSON.');
        $result = $msg->toApi();

        $this->assertSame('developer', $result[0]['role']);
        $this->assertSame('Always respond in JSON.', $result[0]['content']);
    }

    public function testToolRoleMessage(): void
    {
        $msg = new Message(role: Role::tool, message: '{"temperature": 22}', toolCallId: 'call_abc123');
        $result = $msg->toApi();

        $this->assertCount(1, $result);
        $this->assertSame('tool', $result[0]['role']);
        $this->assertSame('{"temperature": 22}', $result[0]['content']);
        $this->assertSame('call_abc123', $result[0]['tool_call_id']);
    }

    public function testToolCallIdAbsentWhenNull(): void
    {
        $msg = new Message(role: Role::user, message: 'Hello', toolCallId: null);
        $result = $msg->toApi();

        $this->assertArrayNotHasKey('tool_call_id', $result[0]);
    }

    public function testNullContentAssistantMessage(): void
    {
        // An assistant message with null content is valid when it only has tool_calls
        $toolCalls = [
            ['id' => 'call_abc', 'type' => 'function', 'function' => ['name' => 'get_weather', 'arguments' => '{"location":"Paris"}']],
        ];
        $msg = new Message(role: Role::assistant, message: null, toolCalls: $toolCalls);
        $result = $msg->toApi();

        $this->assertCount(1, $result);
        $this->assertSame('assistant', $result[0]['role']);
        $this->assertNull($result[0]['content']);
        $this->assertSame($toolCalls, $result[0]['tool_calls']);
    }

    public function testAssistantMessageWithToolCalls(): void
    {
        // An assistant message that has both text and tool_calls
        $toolCalls = [
            ['id' => 'call_xyz', 'type' => 'function', 'function' => ['name' => 'search', 'arguments' => '{"q":"php"}']],
        ];
        $msg = new Message(role: Role::assistant, message: 'Let me search that.', toolCalls: $toolCalls);
        $result = $msg->toApi();

        $this->assertSame('assistant', $result[0]['role']);
        $this->assertSame('Let me search that.', $result[0]['content']);
        $this->assertSame($toolCalls, $result[0]['tool_calls']);
    }

    public function testDelimiterSplitsIntoMultipleMessages(): void
    {
        $msg = new Message(role: Role::user, message: 'part1|part2|part3', delimiter: '|');
        $result = $msg->toApi();

        $this->assertCount(3, $result);
        $this->assertSame('part1', $result[0]['content']);
        $this->assertSame('part2', $result[1]['content']);
        $this->assertSame('part3', $result[2]['content']);

        foreach ($result as $entry) {
            $this->assertSame('user', $entry['role']);
        }
    }

    public function testMessageExceedingMaxTokensSplits(): void
    {
        // 5 words per chunk max; feed 12 words
        $words = implode(' ', array_fill(0, 12, 'word'));
        $msg = new Message(role: Role::user, message: $words, maxTokens: 5);
        $result = $msg->toApi();

        $this->assertGreaterThan(1, count($result));
        foreach ($result as $entry) {
            $this->assertSame('user', $entry['role']);
            $this->assertArrayHasKey('content', $entry);
        }
    }
}
