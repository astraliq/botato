<?php

declare(strict_types=1);

namespace App\Domain\Ai;

enum AiApiStyle: string
{
    case OpenAiCompatible = 'openai_compatible';
    case AnthropicMessages = 'anthropic_messages';
}
