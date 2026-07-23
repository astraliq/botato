<?php

declare(strict_types=1);

namespace App\Application\Ai;

interface AiChatClientInterface
{
    /**
     * @param list<AiChatMessage> $messages
     */
    public function chat(array $messages, ?int $maxTokens = null): AiChatResult;
}
