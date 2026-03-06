---
id: tools
title: Tools (Function Calling)
sidebar_label: Tools
sidebar_position: 4
---

# Tools (Function Calling)

The tools API lets the model call functions you define. You describe the functions and their parameters; the model decides when to call them and with which arguments.

## Classes

| Class | Purpose |
|---|---|
| `Tool` | Wraps a `ToolFunction` with `type: "function"` |
| `ToolFunction` | Defines the function name, description, and parameters |
| `ToolParameter` | Defines a single JSON Schema property for a function parameter |
| `ToolChoice` | Enum controlling how the model selects tools |

## ToolParameter

Represents one parameter of a function, following [JSON Schema](https://json-schema.org/).

```php
use ByJG\LlmApiObjects\Tool\ToolParameter;

new ToolParameter(
    name: string,         // Parameter name (used as property key)
    type: string,         // JSON Schema type: string, number, integer, boolean, array, object
    description: string,  // Human-readable description (optional)
    required: bool,       // Whether this parameter is required (default: false)
    enum: array,          // Allowed values (optional)
)
```

`toSchema()` returns the JSON Schema fragment for this parameter (without the `name`, which is used as the property key by `ToolFunction`).

### Examples

```php
// Required string
new ToolParameter(name: 'location', type: 'string', description: 'City name', required: true)

// Optional enum
new ToolParameter(name: 'unit', type: 'string', enum: ['celsius', 'fahrenheit'])

// Required integer
new ToolParameter(name: 'limit', type: 'integer', description: 'Max results', required: true)
```

## ToolFunction

Defines the function the model can call.

```php
use ByJG\LlmApiObjects\Tool\ToolFunction;

new ToolFunction(
    name: string,              // Function name (snake_case recommended)
    description: string,       // What the function does (optional but strongly recommended)
    parameters: ToolParameter[], // List of parameters (optional)
)
```

`toApi()` produces the `function` object within a tool definition:

```json
{
  "name": "get_current_weather",
  "description": "Get the current weather in a given location",
  "parameters": {
    "type": "object",
    "properties": {
      "location": { "type": "string", "description": "City name" },
      "unit":     { "type": "string", "enum": ["celsius", "fahrenheit"] }
    },
    "required": ["location"]
  }
}
```

The `required` array is only included when at least one parameter has `required: true`. The `description` key is only included when non-empty.

## Tool

Wraps a `ToolFunction` with the `type: "function"` envelope required by the API.

```php
use ByJG\LlmApiObjects\Tool\Tool;

$tool = new Tool(
    function: ToolFunction,
    type: string,          // Always "function" (default)
)
```

`toApi()` produces:

```json
{
  "type": "function",
  "function": { ... }
}
```

### `asChoice()`

Returns the specific-function `tool_choice` object for use with `setToolChoice()`:

```php
$tool->asChoice();
// ["type" => "function", "function" => ["name" => "get_current_weather"]]
```

## ToolChoice

Controls how the model selects tools. Set via `Chat::setToolChoice()`.

```php
use ByJG\LlmApiObjects\Enum\ToolChoice;
```

| Case | API value | Behaviour |
|---|---|---|
| `ToolChoice::auto` | `"auto"` | Model decides whether to call a tool |
| `ToolChoice::none` | `"none"` | Model never calls a tool |
| `ToolChoice::required` | `"required"` | Model must call at least one tool |

To force a specific function, pass the result of `Tool::asChoice()`:

```php
$chat->setToolChoice($weatherTool->asChoice());
// Produces: {"type": "function", "function": {"name": "get_current_weather"}}
```

## Full Example: Weather Tool

```php
<?php
use ByJG\LlmApiObjects\Enum\Role;
use ByJG\LlmApiObjects\Enum\ToolChoice;
use ByJG\LlmApiObjects\Model\Chat;
use ByJG\LlmApiObjects\Model\Message;
use ByJG\LlmApiObjects\Tool\Tool;
use ByJG\LlmApiObjects\Tool\ToolFunction;
use ByJG\LlmApiObjects\Tool\ToolParameter;

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

// First call: model may respond with tool_calls
$response = $client->chat()->create($chat->toApi());
$assistantMessage = $response->choices[0]->message;

if (!empty($assistantMessage->toolCalls)) {
    $toolCall = $assistantMessage->toolCalls[0];
    $args = json_decode($toolCall->function->arguments, true);

    // Execute the actual function
    $weatherResult = getWeather($args['location'], $args['unit'] ?? 'celsius');

    // Build the follow-up request with the tool result
    $followUp = new Chat(
        model: 'gpt-4o',
        messages: [
            new Message(role: Role::user, message: "What's the weather like in Boston?"),
            new Message(
                role: Role::assistant,
                message: null,
                toolCalls: array_map(fn($tc) => $tc->toArray(), $assistantMessage->toolCalls),
            ),
            new Message(
                role: Role::tool,
                message: json_encode($weatherResult),
                toolCallId: $toolCall->id,
            ),
        ],
    );
    $followUp->addTool($weatherTool);

    $final = $client->chat()->create($followUp->toApi());
    echo $final->choices[0]->message->content;
}
```

:::info
The `toolCalls` array you pass to the assistant `Message` must match the structure the API returned. Use `$toolCall->toArray()` from the `openai-php/client` response objects to get the correct shape.
:::
