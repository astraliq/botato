<?php

declare(strict_types=1);

namespace App\Application\Ai;

final readonly class AiChatResult
{
    public function __construct(
        public string $content,
        public string $model,
    ) {}
}
