<?php

namespace ByJG\LlmApiObjects\Tool;

class ToolFunction
{
    public function __construct(
        public string $name,
        public string $description = '',
        /** @var ToolParameter[] */
        public array $parameters = [],
    ) {
    }

    public function toApi(): array
    {
        $properties = [];
        $required = [];

        foreach ($this->parameters as $parameter) {
            $properties[$parameter->name] = $parameter->toSchema();
            if ($parameter->required) {
                $required[] = $parameter->name;
            }
        }

        $api = [
            'name' => $this->name,
        ];

        if (!empty($this->description)) {
            $api['description'] = $this->description;
        }

        $api['parameters'] = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if (!empty($required)) {
            $api['parameters']['required'] = $required;
        }

        return $api;
    }
}
