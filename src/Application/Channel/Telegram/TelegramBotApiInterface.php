<?php

declare(strict_types=1);

namespace App\Application\Channel\Telegram;

interface TelegramBotApiInterface
{
    public function getMe(string $botToken): TelegramBotInfo;

    /**
     * @param list<string> $allowedUpdates
     */
    public function setWebhook(
        string $botToken,
        string $url,
        string $secretToken,
        array $allowedUpdates,
    ): void;

    public function sendMessage(
        string $botToken,
        int|string $chatId,
        string $text,
        ?string $businessConnectionId = null,
        ?int $replyToMessageId = null,
    ): void;
}
