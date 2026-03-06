---
id: chat
title: Chat Model
sidebar_label: Chat Model
sidebar_position: 2
---

# Chat Model

The `Chat` class is the main entry point for building Chat Completions requests. It extends the abstract `Model` class and produces a payload compatible with both OpenAI and Ollama (`/v1` endpoint).

```php
use ByJG\LlmApiObjects\Model\Chat;
```

## Constructor

```php
new Chat(
    model: string,           // Model name, e.g. 'gpt-4o', 'llama3'
    messages: Message[],     // Conversation turns
    system: string,          // System prompt (prepended automatically)
    stream: bool,            // Whether to stream the response (default: false)
)
```

All constructor parameters are **optional** with defaults (`model: 'llama3'`, empty messages, no system, `stream: false`).

## `toApi()`

Serializes the chat to the array expected by `openai-php/client`'s `chat()->create()`:

```php
$result = $client->chat()->create($chat->toApi());
```

### What `toApi()` produces

```php
[
    'model'    => 'gpt-4o',
    'messages' => [
        ['role' => 'system',    'content' => '...'],  // from $system
        ['role' => 'user',      'content' => '...'],
        ['role' => 'assistant', 'content' => '...'],
    ],
    'stream'   => false,
    // 'tools'      => [...],       // when tools are added
    // 'tool_choice'=> 'auto',      // when tool_choice is set
    // 'options'    => [...],       // when ModelFileParameters are set (Ollama)
]
```

:::info
When Ollama `ModelFileParameters` are set, the key is named `options` (not `parameters`) because that is the key Ollama's API expects. `Chat::toApi()` performs this remapping automatically.
:::

## System Prompt

Set a system prompt via the `system` constructor argument. It is prepended to `messages` as the first entry with `role=system`:

```php
$chat = new Chat(
    model: 'gpt-4o',
    messages: [
        new Message(role: Role::user, message: 'Hello!'),
    ],
    system: 'You are a helpful assistant. Always respond in English.',
);
```

## Streaming

Set `stream: true` to request a streamed response. Use `createStreamed()` on the client side:

```php
$chat = new Chat(model: 'gpt-4o', messages: [...], stream: true);
$stream = $client->chat()->createStreamed($chat->toApi());

foreach ($stream as $response) {
    echo $response->choices[0]->delta->content;
}
```

## Adding Tools

See [Tools](tools.md) for the full reference. Quick example:

```php
use ByJG\LlmApiObjects\Enum\ToolChoice;
use ByJG\LlmApiObjects\Tool\Tool;
use ByJG\LlmApiObjects\Tool\ToolFunction;
use ByJG\LlmApiObjects\Tool\ToolParameter;

$chat = new Chat(model: 'gpt-4o', messages: [...]);

$chat->addTool(new Tool(
    new ToolFunction(
        name: 'get_weather',
        description: 'Get the current weather',
        parameters: [
            new ToolParameter(name: 'location', type: 'string', required: true),
        ],
    ),
));

$chat->setToolChoice(ToolChoice::auto);
```

## Ollama Model Parameters

Use `setParameter()` to set Ollama-specific generation parameters. These are serialized under the `options` key:

```php
use ByJG\LlmApiObjects\Enum\ModelFileParameters;

$chat->setParameter(ModelFileParameters::temperature, 0.7);
$chat->setParameter(ModelFileParameters::numCtx, 4096);
```

See [Model Parameters](model-parameters.md) for the full list of available parameters and their valid ranges.
