<?php

namespace ByJG\LlmApiObjects\Enum;

enum ToolChoice: string
{
    case Auto = 'auto';
    case None = 'none';
    case Required = 'required';
}
