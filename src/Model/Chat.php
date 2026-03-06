<?php

namespace ByJG\LlmApiObjects\Model;

use ByJG\LlmApiObjects\Enum\Role;
use ByJG\LlmApiObjects\Model\Model;

class Chat extends Model
{
    #[\Override]
    public function toApi(): array
    {
        $apiAr = parent::toApi();
        if (isset($apiAr['parameters'])) {
            $apiAr['options'] = $apiAr['parameters'];
            unset($apiAr['parameters']);
        }

        if (!empty($this->system)) {
            $systemMessage = new Message(role: Role::system, message: $this->system);
            array_unshift($apiAr['messages'], $systemMessage->toApi()[0]);
        }

        return $apiAr;
    }
}