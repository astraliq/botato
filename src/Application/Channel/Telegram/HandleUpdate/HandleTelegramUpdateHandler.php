<?php

declare(strict_types=1);

namespace App\Application\Channel\Telegram\HandleUpdate;

use App\Application\Channel\Telegram\TelegramBotApiInterface;
use App\Domain\Channel\Channel;
use App\Domain\Channel\ChannelRepositoryInterface;
use App\Domain\Channel\ChannelStatus;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use App\Domain\Usage\UsageRepositoryInterface;
use App\Shared\ArrayHelper;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

final class HandleTelegramUpdateHandler
{
    private const LIMIT_REACHED_REPLY =
        'Лимит диалогов на этот месяц исчерпан. Попробуйте позже или обновите тариф в Botato.';

    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly UsageRepositoryInterface $usageRepository,
        private readonly TelegramBotApiInterface $telegramBotApi,
    ) {}

    /**
     * @param array<string, mixed> $update
     */
    public function handle(string $channelId, string $secretTokenHeader, array $update): void
    {
        $channel = $this->channelRepository->findById($channelId);

        if ($channel === null || $channel->getStatus() !== ChannelStatus::Connected) {
            throw new InvalidArgumentException('Канал не найден или не подключён.');
        }

        $expectedSecret = $channel->getWebhookSecret();

        if ($expectedSecret === null || !hash_equals($expectedSecret, $secretTokenHeader)) {
            throw new InvalidArgumentException('Неверный secret token webhook.');
        }

        if (isset($update['business_connection']) && is_array($update['business_connection'])) {
            $this->rememberBusinessConnection($channel, ArrayHelper::toStringKeyArray($update['business_connection']));

            return;
        }

        $incomingMessage = $this->extractIncomingMessage($update);

        if ($incomingMessage === null) {
            return;
        }

        if ($incomingMessage->fromIsBot || $incomingMessage->text === '') {
            return;
        }

        if ($incomingMessage->businessConnectionId !== null) {
            $ownerUserId = $channel->getBusinessOwnerUserId($incomingMessage->businessConnectionId);

            if ($ownerUserId !== null && $incomingMessage->fromUserId === $ownerUserId) {
                return;
            }
        }

        $botToken = $this->resolveBotToken($channel->getId());
        $subscription = $this->subscriptionRepository->findActiveByUserId($channel->getUserId());

        if ($subscription === null) {
            throw new RuntimeException('У пользователя нет активной подписки.');
        }

        $quotaResult = $this->usageRepository->registerPeerMessage(
            userId: $channel->getUserId(),
            peerKey: (string) $incomingMessage->chatId,
            maxDialogsPerMonth: $subscription->getMaxDialogsPerMonth(),
            now: new DateTimeImmutable(),
        );

        if ($quotaResult->isLimitReached()) {
            $this->telegramBotApi->sendMessage(
                botToken: $botToken,
                chatId: $incomingMessage->chatId,
                text: self::LIMIT_REACHED_REPLY,
                businessConnectionId: $incomingMessage->businessConnectionId,
                replyToMessageId: $incomingMessage->messageId,
            );

            return;
        }

        // Заглушка до подключения AI: подтверждает, что webhook и ответ в личке работают.
        $this->telegramBotApi->sendMessage(
            botToken: $botToken,
            chatId: $incomingMessage->chatId,
            text: 'Botato получил сообщение. AI-ответы появятся после подключения базы знаний.',
            businessConnectionId: $incomingMessage->businessConnectionId,
            replyToMessageId: $incomingMessage->messageId,
        );
    }

    /** @param array<string, mixed> $businessConnection */
    private function rememberBusinessConnection(Channel $channel, array $businessConnection): void
    {
        $connectionId = $businessConnection['id'] ?? null;
        $user = $businessConnection['user'] ?? null;

        if (!is_string($connectionId) || $connectionId === '' || !is_array($user)) {
            return;
        }

        $ownerUserId = $user['id'] ?? null;

        if (!is_int($ownerUserId) && !is_string($ownerUserId)) {
            return;
        }

        $channel->rememberBusinessConnection($connectionId, (int) $ownerUserId, new DateTimeImmutable());
        $this->channelRepository->save($channel);
    }

    /** @param array<string, mixed> $update */
    private function extractIncomingMessage(array $update): ?IncomingTelegramMessage
    {
        $updateId = $update['update_id'] ?? null;

        if (!is_int($updateId) && !is_string($updateId)) {
            return null;
        }

        $messagePayload = null;
        $businessConnectionId = null;

        if (isset($update['business_message']) && is_array($update['business_message'])) {
            $messagePayload = ArrayHelper::toStringKeyArray($update['business_message']);
            $rawBusinessConnectionId = $messagePayload['business_connection_id'] ?? null;
            $businessConnectionId = is_string($rawBusinessConnectionId) ? $rawBusinessConnectionId : null;
        } elseif (isset($update['message']) && is_array($update['message'])) {
            $messagePayload = ArrayHelper::toStringKeyArray($update['message']);
        }

        if ($messagePayload === null) {
            return null;
        }

        $text = $messagePayload['text'] ?? null;

        if (!is_string($text) || trim($text) === '') {
            return null;
        }

        $chat = $messagePayload['chat'] ?? null;
        $from = $messagePayload['from'] ?? null;
        $messageId = $messagePayload['message_id'] ?? null;

        if (!is_array($chat) || (!is_int($messageId) && !is_string($messageId))) {
            return null;
        }

        $chatId = $chat['id'] ?? null;

        if (!is_int($chatId) && !is_string($chatId)) {
            return null;
        }

        $fromUserId = null;
        $fromIsBot = false;

        if (is_array($from)) {
            $rawFromId = $from['id'] ?? null;

            if (is_int($rawFromId) || is_string($rawFromId)) {
                $fromUserId = (int) $rawFromId;
            }

            $fromIsBot = ($from['is_bot'] ?? false) === true;
        }

        return new IncomingTelegramMessage(
            updateId: (int) $updateId,
            chatId: $chatId,
            messageId: (int) $messageId,
            text: trim($text),
            fromUserId: $fromUserId,
            fromIsBot: $fromIsBot,
            businessConnectionId: $businessConnectionId,
        );
    }

    private function resolveBotToken(string $channelId): string
    {
        $secretPayloadJson = $this->channelRepository->getSecretPayload($channelId);

        if ($secretPayloadJson === null || $secretPayloadJson === '') {
            throw new RuntimeException('Секрет канала не найден.');
        }

        try {
            $payload = json_decode($secretPayloadJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Некорректный секрет канала.', previous: $exception);
        }

        if (!is_array($payload)) {
            throw new RuntimeException('Некорректный секрет канала.');
        }

        $botToken = $payload['bot_token'] ?? null;

        if (!is_string($botToken) || $botToken === '') {
            throw new RuntimeException('Токен бота не найден.');
        }

        return $botToken;
    }
}
