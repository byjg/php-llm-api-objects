<?php

namespace ByJG\LlmApiObjects\Tool;

class ToolParameter
{
    public function __construct(
        public string $name,
        public string $type,
        public string $description = '',
        public bool $required = false,
        public array $enum = [],
    ) {
    }

    public function toSchema(): array
    {
        $schema = [
            'type' => $this->type,
        ];

        if (!empty($this->description)) {
            $schema['description'] = $this->description;
        }

        if (!empty($this->enum)) {
            $schema['enum'] = $this->enum;
        }

        return $schema;
    }
}
