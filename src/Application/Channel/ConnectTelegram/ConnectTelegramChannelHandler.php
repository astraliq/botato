<?php

declare(strict_types=1);

namespace App\Application\Channel\ConnectTelegram;

use App\Application\Channel\Telegram\TelegramBotApiInterface;
use App\Domain\Channel\Channel;
use App\Domain\Channel\ChannelProvider;
use App\Domain\Channel\ChannelRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;
use Ramsey\Uuid\Uuid;
use RuntimeException;

final class ConnectTelegramChannelHandler
{
    private const ALLOWED_UPDATES = [
        'message',
        'edited_message',
        'business_connection',
        'business_message',
        'edited_business_message',
    ];

    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly TelegramBotApiInterface $telegramBotApi,
        private readonly string $publicBaseUrl,
    ) {}

    public function handle(string $userId, ConnectTelegramChannelCommand $command): Channel
    {
        $botToken = trim($command->botToken);

        if ($botToken === '') {
            throw new InvalidArgumentException('Укажите токен бота.');
        }

        if (!preg_match('/^\d+:[A-Za-z0-9_-]+$/', $botToken)) {
            throw new InvalidArgumentException('Некорректный формат токена Telegram.');
        }

        $publicBaseUrl = rtrim(trim($this->publicBaseUrl), '/');

        if ($publicBaseUrl === '' || !str_starts_with($publicBaseUrl, 'https://')) {
            throw new InvalidArgumentException(
                'Для webhook укажите APP_PUBLIC_URL с HTTPS (например https://botato.example).',
            );
        }

        try {
            $botInfo = $this->telegramBotApi->getMe($botToken);
        } catch (RuntimeException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), previous: $exception);
        }

        $now = new DateTimeImmutable();
        $channel = $this->channelRepository->findConnectedByUserAndProvider(
            $userId,
            ChannelProvider::Telegram,
        );

        if ($channel === null) {
            $channel = Channel::create(
                id: Uuid::uuid4()->toString(),
                userId: $userId,
                provider: ChannelProvider::Telegram,
                createdAt: $now,
            );
        }

        $displayName = str_starts_with($botInfo->username, '@')
            ? $botInfo->username
            : '@' . $botInfo->username;

        $channel->connectTelegram($botInfo->id, $displayName, $now);

        $webhookSecret = $channel->getWebhookSecret() ?? $this->generateWebhookSecret();
        $channel->setWebhookSecret($webhookSecret, $now);

        try {
            $secretPayloadJson = json_encode(
                ['bot_token' => $botToken],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('Не удалось подготовить секрет канала.', previous: $exception);
        }

        $this->channelRepository->save($channel, $secretPayloadJson);

        $webhookUrl = $publicBaseUrl . '/webhooks/telegram/' . $channel->getId();

        try {
            $this->telegramBotApi->setWebhook(
                $botToken,
                $webhookUrl,
                $webhookSecret,
                self::ALLOWED_UPDATES,
            );
        } catch (RuntimeException | InvalidArgumentException $exception) {
            throw new InvalidArgumentException(
                'Бот сохранён, но webhook не установлен: ' . $exception->getMessage(),
                previous: $exception,
            );
        }

        return $channel;
    }

    private function generateWebhookSecret(): string
    {
        return bin2hex(random_bytes(24));
    }
}
