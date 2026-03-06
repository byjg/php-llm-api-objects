<?php

namespace ByJG\LlmApiObjects\Model;

use ByJG\LlmApiObjects\Enum\ModelFileParameters;
use ByJG\LlmApiObjects\Enum\ToolChoice;
use ByJG\LlmApiObjects\Tool\Tool;
use InvalidArgumentException;

abstract class Model
{
    protected array $parameters = [];
    /** @var Tool[] */
    protected array $tools = [];
    protected ToolChoice|array|null $toolChoice = null;

    public function __construct(
        public string $model = 'llama3',
        public array $messages = [],
        public string $system = '',
        public bool $stream = false,
    )
    {
    }

    public function setParameter(ModelFileParameters $name, mixed $value): void
    {
        if ($name->isValid($value)) {
            $this->parameters[$name->value] = $value;
        } else {
            throw new InvalidArgumentException("Invalid value for parameter {$name->value}");
        }
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function addTool(Tool $tool): void
    {
        $this->tools[] = $tool;
    }

    public function setToolChoice(ToolChoice|array $choice): void
    {
        $this->toolChoice = $choice;
    }

    public function toApi(): array
    {
        $messages = [];
        /** @var Message $message */
        foreach ($this->messages as $message) {
            $messages = array_merge($messages, $message->toApi());
        }

        $api = [
            'model' => $this->model,
            'messages' => $messages,
            'stream' => $this->stream,
        ];

        if (!empty($this->parameters)) {
            $api['parameters'] = $this->parameters;
        }

        if (!empty($this->tools)) {
            $api['tools'] = array_map(fn(Tool $t) => $t->toApi(), $this->tools);
        }

        if ($this->toolChoice !== null) {
            $api['tool_choice'] = $this->toolChoice instanceof ToolChoice
                ? $this->toolChoice->value
                : $this->toolChoice;
        }

        return $api;

    }
}