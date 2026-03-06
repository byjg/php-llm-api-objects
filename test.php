<?php

use ByJG\OpenApiModels\Enum\Role;
use ByJG\OpenApiModels\Model\Chat;
use ByJG\OpenApiModels\Model\Message;

require __DIR__ . '/vendor/autoload.php';

// $client = OpenAI::client("ollama");

$client = OpenAI::factory()
    ->withApiKey("ollama")
    ->withBaseUri('http://localhost:11434/v1') // default: api.openai.com/v1
    ->make();

$model = new Chat(
    model: 'llama3',
    messages: [
        new Message(
            role: Role::user,
            message: 'Hello! My name is John. I am a software developer.',
        ),
        new Message(
            role: Role::assistant,
            message: 'Nice to meet you, John.',
        ),
        new Message(
            role: Role::user,
            message: 'What is my name?',
        ),
    ],
    system: "You are a geek and shy away from social interactions.",
);

print_r($model->toApi());

$result = $client->chat()->create($model->toApi());

echo $result->choices[0]->message->content; // Hello! How can I assist you today?