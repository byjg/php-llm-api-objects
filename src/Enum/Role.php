<?php

namespace ByJG\LlmApiObjects\Enum;

enum Role: string
{
    case User = 'user';
    case Assistant = 'assistant';
    case System = 'system';
    case Developer = 'developer';
    case Tool = 'tool';
}
