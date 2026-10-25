---
sidebar_key: llm-api-objects
sidebar_category: text-content
tags: [php, ai]
---

# LLM API Objects

Typed PHP objects for Chat Completions requests (messages, tools, tool calls) that work with both OpenAI and Ollama.
Works with both OpenAI and Ollama (via the `/v1` OpenAI-compatible endpoint) using the [`openai-php/client`](https://github.com/openai-php/client).

[![Sponsor](https://img.shields.io/badge/Sponsor-%23ea4aaa?logo=githubsponsors&logoColor=white&labelColor=0d1117)](https://github.com/sponsors/byjg)
[![Build Status](https://github.com/byjg/php-llm-api-objects/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/php-llm-api-objects/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](https://opensource.byjg.com)
[![Install MCP Server](https://img.shields.io/badge/Install-MCP_Server-8A2BE2?logo=modelcontextprotocol&logoColor=white)](https://opensource.byjg.com/docs/ai/mcpserver-byjg-docs/)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/php-llm-api-objects/)
[![GitHub license](https://img.shields.io/github/license/byjg/php-llm-api-objects.svg)](https://opensource.byjg.com/license/)
[![GitHub release](https://img.shields.io/github/release/byjg/php-llm-api-objects.svg)](https://github.com/byjg/php-llm-api-objects/releases/)

## Why

A chat completion request is a deeply nested array: roles, content, tools with JSON-schema
parameters, tool calls and their ids. Built by hand, a typo becomes an API error at runtime,
and a long document has to be cut to fit the context by you.

LLM API Objects builds the request from typed classes (`Chat`, `Message`, `Tool`,
`ToolFunction`, `ToolParameter`), so the IDE and static analysis catch the mistakes before
the call.

- **OpenAI and Ollama with the same objects**, through Ollama's OpenAI-compatible endpoint.
- **Long content split for you.** A message is split into chunks by a delimiter and by an
  approximate token limit (`maxTokens`).

## Features

- Strongly-typed models for Chat Completions requests
- Full support for function calling (tools): `Tool`, `ToolFunction`, `ToolParameter`, `ToolChoice`
- Multi-turn conversations with `role=tool` messages and `tool_call_id`
- Automatic message splitting when content exceeds a token limit
- Delimiter-based message partitioning
- Compatible with OpenAI and Ollama (`/v1` endpoint)
- Ollama model tuning parameters via `ModelFileParameters`
- Jira XML/RSS importers to seed conversation context

## Documentation

- [Getting Started](docs/getting-started.md) — Installation and first request
- [Chat Model](docs/chat.md) — Building and sending chat requests
- [Messages](docs/messages.md) — Roles, content, `tool_call_id`, `tool_calls`
- [Tools](docs/tools.md) — Function calling: Tool, ToolFunction, ToolParameter, ToolChoice
- [Model Parameters](docs/model-parameters.md) — Ollama model tuning parameters
- [Importers](docs/importers.md) — Jira XML/RSS importers

## Quick Examples

### Basic chat with OpenAI

```php
<?php
require 'vendor/autoload.php';

$client = OpenAI::factory()
    ->withApiKey($_ENV['OPENAI_API_KEY'])
    ->make();

$chat = new \ByJG\LlmApiObjects\Model\Chat(
    model: 'gpt-4o',
    messages: [
        new \ByJG\LlmApiObjects\Model\Message(
            role: \ByJG\LlmApiObjects\Enum\Role::User,
            message: 'What is the capital of France?',
        ),
    ],
    system: 'You are a helpful geography assistant.',
);

$result = $client->chat()->create($chat->toApi());
echo $result->choices[0]->message->content;
```

### Basic chat with Ollama

```php
<?php
$client = OpenAI::factory()
    ->withApiKey('ollama')
    ->withBaseUri('http://localhost:11434/v1')
    ->make();

$chat = new \ByJG\LlmApiObjects\Model\Chat(
    model: 'llama3',
    messages: [
        new \ByJG\LlmApiObjects\Model\Message(
            role: \ByJG\LlmApiObjects\Enum\Role::User,
            message: 'Hello!',
        ),
    ],
);

$result = $client->chat()->create($chat->toApi());
echo $result->choices[0]->message->content;
```

### Function calling (tools)

```php
<?php
use ByJG\LlmApiObjects\Enum\ToolChoice;
use ByJG\LlmApiObjects\Model\Chat;
use ByJG\LlmApiObjects\Model\Message;
use ByJG\LlmApiObjects\Enum\Role;
use ByJG\LlmApiObjects\Tool\Tool;
use ByJG\LlmApiObjects\Tool\ToolFunction;
use ByJG\LlmApiObjects\Tool\ToolParameter;

$weatherTool = new Tool(
    new ToolFunction(
        name: 'get_current_weather',
        description: 'Get the current weather in a given location',
        parameters: [
            new ToolParameter(name: 'location', type: 'string', description: 'City name', required: true),
            new ToolParameter(name: 'unit', type: 'string', enum: ['celsius', 'fahrenheit']),
        ],
    ),
);

$chat = new Chat(
    model: 'gpt-4o',
    messages: [
        new Message(role: Role::User, message: "What's the weather in Paris?"),
    ],
);
$chat->addTool($weatherTool);
$chat->setToolChoice(ToolChoice::Auto);

$result = $client->chat()->create($chat->toApi());
```

## Installation

```bash
composer require byjg/llm-api-objects
```

## Running Tests

```bash
composer test
```

----
[Open source ByJG](https://opensource.byjg.com)
