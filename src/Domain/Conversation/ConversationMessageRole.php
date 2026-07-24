<?php

declare(strict_types=1);

namespace App\Domain\Conversation;

enum ConversationMessageRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
}
