---
id: messages
title: Messages
sidebar_label: Messages
sidebar_position: 3
---

# Messages

The `Message` class represents a single turn in a conversation.

```php
use ByJG\LlmApiObjects\Model\Message;
use ByJG\LlmApiObjects\Enum\Role;
```

## Constructor

```php
new Message(
    role: Role,             // Role of the message author
    message: ?string,       // Text content (null for tool-call-only assistant turns)
    delimiter: string,      // Split content on this string into multiple messages
    maxTokens: int,         // Max tokens per message chunk (default: 2048)
    toolCallId: ?string,    // ID linking a role=tool message back to the tool call
    toolCalls: array,       // Tool calls made by the assistant
)
```

## Roles

The `Role` enum defines all valid message roles:

| Case | API value | When to use |
|---|---|---|
| `Role::user` | `user` | End-user input |
| `Role::assistant` | `assistant` | Model response |
| `Role::system` | `system` | System instructions (prefer the `system` param on `Chat`) |
| `Role::developer` | `developer` | Application-level instructions, between system and user in priority |
| `Role::tool` | `tool` | Tool execution result (requires `toolCallId`) |

:::tip
For system-level instructions, prefer the `system` constructor argument on `Chat` rather than creating a `Message` with `Role::system` manually. The `Chat` model prepends it automatically in `toApi()`.
:::

## Basic Usage

```php
new Message(role: Role::user, message: 'Hello, how are you?')
new Message(role: Role::assistant, message: 'I am doing well, thank you!')
new Message(role: Role::system, message: 'Always be concise.')
new Message(role: Role::developer, message: 'Always respond in JSON.')
```

## Tool Result Messages (role=tool)

When the model responds with a tool call, you must send the result back using `role=tool` with the `toolCallId` that links it to the original call:

```php
new Message(
    role: Role::tool,
    message: '{"temperature": 22, "unit": "celsius"}',
    toolCallId: 'call_abc123',
)
```

`toApi()` will include `tool_call_id` in the serialized array.

## Assistant Messages with Tool Calls

When reconstructing a multi-turn conversation that involved tool calls, you must include the assistant's prior message that contained the `tool_calls` array. The content is typically `null` in this case:

```php
$toolCalls = [
    [
        'id'       => 'call_abc123',
        'type'     => 'function',
        'function' => ['name' => 'get_weather', 'arguments' => '{"location":"Paris"}'],
    ],
];

new Message(
    role: Role::assistant,
    message: null,         // null content is valid when tool_calls are present
    toolCalls: $toolCalls,
)
```

The `toolCalls` array must match the structure returned by the OpenAI API in the response message.

## Complete Multi-turn Tool Conversation

```php
use ByJG\LlmApiObjects\Enum\Role;
use ByJG\LlmApiObjects\Model\Chat;
use ByJG\LlmApiObjects\Model\Message;

$toolCalls = [
    ['id' => 'call_abc123', 'type' => 'function', 'function' => ['name' => 'get_weather', 'arguments' => '{"location":"Paris"}']],
];

$chat = new Chat(
    model: 'gpt-4o',
    messages: [
        // Turn 1: user asks
        new Message(role: Role::user, message: "What's the weather in Paris?"),
        // Turn 2: assistant responds with a tool call (null content)
        new Message(role: Role::assistant, message: null, toolCalls: $toolCalls),
        // Turn 3: tool result
        new Message(role: Role::tool, message: '{"temperature":22,"unit":"celsius"}', toolCallId: 'call_abc123'),
    ],
);

// Send again to get the final assistant response
$result = $client->chat()->create($chat->toApi());
echo $result->choices[0]->message->content;
```

## Message Splitting

### By Token Count

`explodeMessage()` splits content into chunks that do not exceed `maxTokens`. Each chunk becomes a separate message in the `toApi()` output. This is useful when feeding large text blobs that need to stay within context limits:

```php
new Message(
    role: Role::user,
    message: $veryLongText,
    maxTokens: 1000,
)
```

:::warning
Token counting uses `str_word_count()`, which is a word-count approximation — not an actual tokenizer. For precise control, pre-split content yourself before creating `Message` objects.
:::

### By Delimiter

Set `delimiter` to split the message on a string. Each part is treated as an independent message with the same role, and each part is still subject to `maxTokens` splitting:

```php
new Message(
    role: Role::user,
    message: "part one|part two|part three",
    delimiter: '|',
)
// Produces three separate messages in toApi()
```
