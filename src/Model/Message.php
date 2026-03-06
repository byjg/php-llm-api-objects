<?php

namespace ByJG\LlmApiObjects\Model;

use ByJG\LlmApiObjects\Enum\Role;

class Message
{
    public function __construct(
        public Role $role = Role::user,
        public ?string $message = '',
        public string $delimiter = '',
        public int $maxTokens = 2048,
        public ?string $toolCallId = null,
        /**
         * Used for role=assistant messages that triggered tool calls.
         * Each entry: ['id' => string, 'type' => 'function', 'function' => ['name' => string, 'arguments' => string]]
         * @var array<int, array{id: string, type: string, function: array{name: string, arguments: string}}>
         */
        public array $toolCalls = [],
    ) {
    }

    // $messagePart cannot exceed $maxTokens, if the messagePart exceeds the $maxTokens,
    // it will be split into another message
    // The delimiter will not be used in this method
    protected function explodeMessage(string $messagePart): array
    {
        $messages = [];
        $currentMessage = '';
        $tokenCount = 0;

        foreach (explode(' ', $messagePart) as $word) {
            $wordTokenCount = str_word_count($word);
            if ($tokenCount + $wordTokenCount > $this->maxTokens) {
                $entry = [
                    'role' => $this->role->value,
                    'content' => $currentMessage,
                ];
                if ($this->toolCallId !== null) {
                    $entry['tool_call_id'] = $this->toolCallId;
                }
                $messages[] = $entry;
                $currentMessage = $word;
                $tokenCount = $wordTokenCount;
            } else {
                $currentMessage .= (!empty($currentMessage) ? ' ' : '') . $word;
                $tokenCount += $wordTokenCount;
            }
        }

        if (!empty($currentMessage)) {
            $entry = [
                'role' => $this->role->value,
                'content' => $currentMessage,
            ];
            if ($this->toolCallId !== null) {
                $entry['tool_call_id'] = $this->toolCallId;
            }
            $messages[] = $entry;
        }

        return $messages;
    }

    public function toApi(): array
    {
        // Assistant message with tool_calls and no text content (null content)
        if ($this->message === null) {
            $entry = [
                'role' => $this->role->value,
                'content' => null,
            ];
            if (!empty($this->toolCalls)) {
                $entry['tool_calls'] = $this->toolCalls;
            }
            if ($this->toolCallId !== null) {
                $entry['tool_call_id'] = $this->toolCallId;
            }
            return [$entry];
        }

        if (!empty($this->delimiter)) {
            $parts = explode($this->delimiter, $this->message);
        } else {
            $parts = [$this->message];
        }

        $messages = [];
        foreach ($parts as $part) {
            $messages = array_merge($messages, $this->explodeMessage($part));
        }

        // Attach tool_calls to the last message fragment if present
        if (!empty($this->toolCalls) && !empty($messages)) {
            $messages[count($messages) - 1]['tool_calls'] = $this->toolCalls;
        }

        return $messages;
    }
}
