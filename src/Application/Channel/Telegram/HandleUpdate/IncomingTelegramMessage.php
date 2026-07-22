<?php

declare(strict_types=1);

namespace App\Application\Channel\Telegram\HandleUpdate;

final readonly class IncomingTelegramMessage
{
    public function __construct(
        public int $updateId,
        public int|string $chatId,
        public int $messageId,
        public string $text,
        public ?int $fromUserId,
        public bool $fromIsBot,
        public ?string $businessConnectionId,
    ) {}
}
