<?php

declare(strict_types=1);

namespace App\Application\Ai;

final readonly class AiChatMessage
{
    public function __construct(
        public string $role,
        public string $content,
    ) {}
}
