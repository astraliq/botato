<?php

declare(strict_types=1);

namespace App\Application\Channel\Telegram\HandleUpdate;

use App\Domain\Channel\Channel;
use App\Domain\Channel\ChannelRepositoryInterface;
use App\Domain\Channel\ChannelStatus;
use App\Domain\Queue\QueueJobRepositoryInterface;
use App\Domain\Queue\QueueJobType;
use App\Shared\ArrayHelper;
use DateTimeImmutable;
use InvalidArgumentException;

final class HandleTelegramUpdateHandler
{
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly QueueJobRepositoryInterface $queueJobRepository,
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

        $this->queueJobRepository->enqueue(
            queue: 'default',
            jobType: QueueJobType::TELEGRAM_PROCESS_MESSAGE,
            payload: [
                'channel_id' => $channelId,
                'update_id' => $incomingMessage->updateId,
                'chat_id' => $incomingMessage->chatId,
                'message_id' => $incomingMessage->messageId,
                'text' => $incomingMessage->text,
                'business_connection_id' => $incomingMessage->businessConnectionId,
                'from_user_id' => $incomingMessage->fromUserId,
            ],
            dedupeKey: $channelId . ':' . $incomingMessage->updateId,
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
}
