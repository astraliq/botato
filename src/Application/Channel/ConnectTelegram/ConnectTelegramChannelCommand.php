<?php

declare(strict_types=1);

namespace App\Application\Channel\ConnectTelegram;

final readonly class ConnectTelegramChannelCommand
{
    public function __construct(
        public string $botToken,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $botToken = $data['bot_token'] ?? '';

        return new self(
            botToken: is_string($botToken) ? $botToken : '',
        );
    }
}
