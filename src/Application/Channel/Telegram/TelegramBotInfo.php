<?php

declare(strict_types=1);

namespace App\Application\Channel\Telegram;

final readonly class TelegramBotInfo
{
    public function __construct(
        public string $id,
        public string $username,
    ) {}
}
