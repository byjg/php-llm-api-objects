<?php

namespace ByJG\LlmApiObjects\Enum;

enum ToolChoice: string
{
    case auto = 'auto';
    case none = 'none';
    case required = 'required';
}
