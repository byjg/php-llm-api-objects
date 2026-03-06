<?php

namespace ByJG\LlmApiObjects\Enum;

enum Role: string
{
    case user = 'user';
    case assistant = 'assistant';
    case system = 'system';
    case developer = 'developer';
    case tool = 'tool';
}
