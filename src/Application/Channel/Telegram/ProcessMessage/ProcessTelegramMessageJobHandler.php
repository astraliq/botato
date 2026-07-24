<?php

declare(strict_types=1);

namespace App\Application\Channel\Telegram\ProcessMessage;

use App\Application\Ai\AiChatClientInterface;
use App\Application\Ai\AiChatMessage;
use App\Application\Channel\Telegram\TelegramBotApiInterface;
use App\Domain\Channel\ChannelRepositoryInterface;
use App\Domain\Channel\ChannelStatus;
use App\Domain\Conversation\ConversationMessageRepositoryInterface;
use App\Domain\Conversation\ConversationMessageRole;
use App\Domain\KnowledgeBase\KnowledgeArticleRepositoryInterface;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use App\Domain\Usage\UsageRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use DateTimeImmutable;
use JsonException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class ProcessTelegramMessageJobHandler
{
    private const LIMIT_REACHED_REPLY =
        'Лимит диалогов на этот месяц исчерпан. Попробуйте позже или обновите тариф в Botato.';

    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly UsageRepositoryInterface $usageRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly KnowledgeArticleRepositoryInterface $knowledgeArticleRepository,
        private readonly ConversationMessageRepositoryInterface $conversationMessageRepository,
        private readonly AiChatClientInterface $aiChatClient,
        private readonly TelegramBotApiInterface $telegramBotApi,
        private readonly LoggerInterface $logger,
    ) {}

    /** @param array<string, mixed> $payload */
    public function handle(array $payload): void
    {
        $channelId = $payload['channel_id'] ?? null;
        $chatId = $payload['chat_id'] ?? null;
        $messageId = $payload['message_id'] ?? null;
        $text = $payload['text'] ?? null;
        $businessConnectionId = $payload['business_connection_id'] ?? null;

        if (!is_string($channelId) || $channelId === '') {
            throw new RuntimeException('В payload отсутствует channel_id.');
        }

        if (!is_int($chatId) && !is_string($chatId)) {
            throw new RuntimeException('В payload отсутствует chat_id.');
        }

        if (!is_int($messageId) && !is_string($messageId)) {
            throw new RuntimeException('В payload отсутствует message_id.');
        }

        if (!is_string($text) || trim($text) === '') {
            throw new RuntimeException('В payload отсутствует text.');
        }

        if ($businessConnectionId !== null && !is_string($businessConnectionId)) {
            $businessConnectionId = null;
        }

        $channel = $this->channelRepository->findById($channelId);

        if ($channel === null || $channel->getStatus() !== ChannelStatus::Connected) {
            throw new RuntimeException('Канал не найден или не подключён.');
        }

        $botToken = $this->resolveBotToken($channelId);
        $subscription = $this->subscriptionRepository->findActiveByUserId($channel->getUserId());

        if ($subscription === null) {
            throw new RuntimeException('У пользователя нет активной подписки.');
        }

        $peerKey = (string) $chatId;
        $messagesPerDialog = max(1, $subscription->getMessagesPerDialog());

        $quotaResult = $this->usageRepository->registerPeerMessage(
            userId: $channel->getUserId(),
            peerKey: $peerKey,
            maxDialogsPerMonth: $subscription->getMaxDialogsPerMonth(),
            messagesPerDialog: $messagesPerDialog,
            now: new DateTimeImmutable(),
        );

        if ($quotaResult->isLimitReached()) {
            $this->telegramBotApi->sendMessage(
                botToken: $botToken,
                chatId: $chatId,
                text: self::LIMIT_REACHED_REPLY,
                businessConnectionId: $businessConnectionId,
                replyToMessageId: (int) $messageId,
            );

            return;
        }

        $user = $this->userRepository->findById($channel->getUserId());

        if ($user === null) {
            throw new RuntimeException('Пользователь канала не найден.');
        }

        $trimmedText = trim($text);

        $this->conversationMessageRepository->append(
            channelId: $channelId,
            peerKey: $peerKey,
            role: ConversationMessageRole::User,
            content: $trimmedText,
            externalId: (string) $messageId,
        );

        $history = $this->conversationMessageRepository->listRecent(
            channelId: $channelId,
            peerKey: $peerKey,
            limit: $messagesPerDialog,
        );

        $systemPrompt = $this->buildSystemPrompt(
            companyName: $user->getCompanyName(),
            description: $user->getDescription(),
            knowledgeBaseIds: $channel->getKnowledgeBaseIds(),
        );

        $chatMessages = [
            new AiChatMessage(role: 'system', content: $systemPrompt),
        ];

        foreach ($history as $historyMessage) {
            $chatMessages[] = new AiChatMessage(
                role: $historyMessage->getRole()->value,
                content: $historyMessage->getContent(),
            );
        }

        try {
            $aiResult = $this->aiChatClient->chat($chatMessages);
        } catch (Throwable $exception) {
            $this->logger->error('AI chat failed for Telegram message.', [
                'channel_id' => $channelId,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $this->conversationMessageRepository->append(
            channelId: $channelId,
            peerKey: $peerKey,
            role: ConversationMessageRole::Assistant,
            content: $aiResult->content,
        );

        $this->telegramBotApi->sendMessage(
            botToken: $botToken,
            chatId: $chatId,
            text: $aiResult->content,
            businessConnectionId: $businessConnectionId,
            replyToMessageId: (int) $messageId,
        );
    }

    /**
     * @param list<string> $knowledgeBaseIds
     */
    private function buildSystemPrompt(string $companyName, string $description, array $knowledgeBaseIds): string
    {
        $parts = [
            'Ты ИИ-ассистент бизнеса в мессенджере. Отвечай кратко, вежливо и по делу на русском языке.',
            'Не выдумывай факты, которых нет в описании бизнеса и базе знаний.',
        ];

        if ($companyName !== '') {
            $parts[] = 'Название бизнеса: ' . $companyName;
        }

        if ($description !== '') {
            $parts[] = "Описание бизнеса:\n" . $description;
        }

        if ($knowledgeBaseIds !== []) {
            $articles = $this->knowledgeArticleRepository->listActiveForPromptByKnowledgeBaseIds($knowledgeBaseIds);
            $knowledgeChunks = [];

            foreach ($articles as $article) {
                $knowledgeChunks[] = '## ' . $article->getTitle() . "\n" . $article->getContent();
            }

            if ($knowledgeChunks !== []) {
                $parts[] = "База знаний:\n" . implode("\n\n", $knowledgeChunks);
            }
        }

        return implode("\n\n", $parts);
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
