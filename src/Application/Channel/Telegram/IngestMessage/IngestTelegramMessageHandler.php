<?php

declare(strict_types=1);

namespace App\Application\Channel\Telegram\IngestMessage;

use App\Application\Channel\Telegram\HandleUpdate\IncomingTelegramMessage;
use App\Application\Channel\Telegram\TelegramBotApiInterface;
use App\Domain\Channel\Channel;
use App\Domain\Channel\ChannelRepositoryInterface;
use App\Domain\Conversation\ConversationMessageRepositoryInterface;
use App\Domain\Conversation\ConversationMessageRole;
use App\Domain\Queue\QueueJobRepositoryInterface;
use App\Domain\Queue\QueueJobType;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use App\Domain\Usage\UsageRepositoryInterface;
use DateTimeImmutable;
use JsonException;
use RuntimeException;

/** Принимает входящее сообщение: квота, сохранение, планирование debounce-ответа. */
final class IngestTelegramMessageHandler
{
    public const MESSAGE_DEBOUNCE_SECONDS = 10;

    private const LIMIT_REACHED_REPLY =
        'Лимит диалогов на этот месяц исчерпан. Попробуйте позже или обновите тариф в Botato.';

    private const MESSAGES_LIMIT_REACHED_REPLY =
        'Лимит сообщений в этом диалоге исчерпан. Новый диалог с этим клиентом начнётся через 24 часа.';

    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly UsageRepositoryInterface $usageRepository,
        private readonly ConversationMessageRepositoryInterface $conversationMessageRepository,
        private readonly QueueJobRepositoryInterface $queueJobRepository,
        private readonly TelegramBotApiInterface $telegramBotApi,
    ) {}

    public function handle(Channel $channel, IncomingTelegramMessage $incomingMessage): void
    {
        $channelId = $channel->getId();
        $peerKey = (string) $incomingMessage->chatId;
        $externalId = (string) $incomingMessage->messageId;

        if ($this->conversationMessageRepository->findByExternalId($channelId, $peerKey, $externalId) !== null) {
            return;
        }

        $botToken = $this->resolveBotToken($channelId);
        $subscription = $this->subscriptionRepository->findActiveByUserId($channel->getUserId());

        if ($subscription === null) {
            throw new RuntimeException('У пользователя нет активной подписки.');
        }

        $now = new DateTimeImmutable();
        $quotaResult = $this->usageRepository->registerPeerMessage(
            userId: $channel->getUserId(),
            peerKey: $peerKey,
            maxDialogsPerMonth: $subscription->getMaxDialogsPerMonth(),
            maxMessagesPerDialog: max(1, $subscription->getMaxMessagesPerDialog()),
            now: $now,
        );

        if ($quotaResult->isLimitReached()) {
            $replyText = $quotaResult->isMessagesLimitReached()
                ? self::MESSAGES_LIMIT_REACHED_REPLY
                : self::LIMIT_REACHED_REPLY;

            $this->telegramBotApi->sendMessage(
                botToken: $botToken,
                chatId: $incomingMessage->chatId,
                text: $replyText,
                businessConnectionId: $incomingMessage->businessConnectionId,
                replyToMessageId: $incomingMessage->messageId,
            );

            return;
        }

        $this->conversationMessageRepository->append(
            channelId: $channelId,
            peerKey: $peerKey,
            role: ConversationMessageRole::User,
            content: $incomingMessage->text,
            externalId: $externalId,
        );

        $availableAt = $now->modify('+' . self::MESSAGE_DEBOUNCE_SECONDS . ' seconds');

        if ($availableAt === false) {
            $availableAt = new DateTimeImmutable('+' . self::MESSAGE_DEBOUNCE_SECONDS . ' seconds');
        }

        $this->queueJobRepository->scheduleOrPostpone(
            queue: 'default',
            jobType: QueueJobType::TELEGRAM_PROCESS_MESSAGE,
            payload: [
                'channel_id' => $channelId,
                'chat_id' => $incomingMessage->chatId,
                'business_connection_id' => $incomingMessage->businessConnectionId,
                'include_until' => $now->format('Y-m-d H:i:sP'),
                'reply_to_message_id' => $incomingMessage->messageId,
            ],
            dedupeKey: 'telegram.debounce:' . $channelId . ':' . $peerKey,
            availableAt: $availableAt,
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
