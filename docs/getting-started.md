---
id: getting-started
title: Getting Started
sidebar_label: Getting Started
sidebar_position: 1
---

# Getting Started

`byjg/llm-api-objects` provides a strongly-typed PHP model layer for building [OpenAI Chat Completions](https://platform.openai.com/docs/api-reference/chat) request payloads. All models serialize to `array` via `toApi()`, ready to pass directly to [`openai-php/client`](https://github.com/openai-php/client).

It works with both **OpenAI** and **Ollama** (via the `/v1` OpenAI-compatible endpoint).

## Installation

```bash
composer require byjg/llm-api-objects
```

## Requirements

- PHP 8.3+

## Connecting to OpenAI

```php
<?php
require 'vendor/autoload.php';

$client = OpenAI::factory()
    ->withApiKey($_ENV['OPENAI_API_KEY'])
    ->make();
```

## Connecting to Ollama

Ollama exposes an OpenAI-compatible `/v1` endpoint. Pass any string as the API key (Ollama does not validate it) and set the base URI:

```php
$client = OpenAI::factory()
    ->withApiKey('ollama')
    ->withBaseUri('http://localhost:11434/v1')
    ->make();
```

## Your First Request

```php
<?php
use ByJG\LlmApiObjects\Enum\Role;
use ByJG\LlmApiObjects\Model\Chat;
use ByJG\LlmApiObjects\Model\Message;

$chat = new Chat(
    model: 'gpt-4o',
    messages: [
        new Message(role: Role::User, message: 'What is the capital of France?'),
    ],
    system: 'You are a helpful assistant.',
);

$result = $client->chat()->create($chat->toApi());

echo $result->choices[0]->message->content;
// Paris
```

:::tip
`toApi()` returns a plain PHP `array`. You can inspect it with `print_r($chat->toApi())` before sending to debug the exact payload.
:::

## Multi-turn Conversation

Pass multiple `Message` objects to build a conversation history:

```php
use ByJG\LlmApiObjects\Enum\Role;
use ByJG\LlmApiObjects\Model\Chat;
use ByJG\LlmApiObjects\Model\Message;

$chat = new Chat(
    model: 'gpt-4o',
    messages: [
        new Message(role: Role::User,      message: 'My name is Alice.'),
        new Message(role: Role::Assistant, message: 'Nice to meet you, Alice!'),
        new Message(role: Role::User,      message: 'What is my name?'),
    ],
);

$result = $client->chat()->create($chat->toApi());
echo $result->choices[0]->message->content;
// Your name is Alice.
```

## Next Steps

- [Chat Model](chat.md) — all Chat options (system, stream, parameters)
- [Messages](messages.md) — roles, token splitting, tool result messages
- [Tools](tools.md) — function calling
- [Model Parameters](model-parameters.md) — Ollama tuning parameters
- [Importers](importers.md) — seed context from Jira exports
