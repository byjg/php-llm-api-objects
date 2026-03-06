<?php

namespace ByJG\LlmApiObjects\Tool;

class Tool
{
    public function __construct(
        public ToolFunction $function,
        public string $type = 'function',
    ) {
    }

    public function toApi(): array
    {
        return [
            'type' => $this->type,
            'function' => $this->function->toApi(),
        ];
    }

    public function asChoice(): array
    {
        return [
            'type' => 'function',
            'function' => ['name' => $this->function->name],
        ];
    }
}
