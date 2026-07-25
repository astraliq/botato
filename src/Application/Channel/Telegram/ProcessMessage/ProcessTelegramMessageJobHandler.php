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
use App\Domain\Usage\DialogBillingRules;
use App\Domain\Usage\UsageRepositoryInterface;
use App\Domain\User\UserGender;
use App\Domain\User\UserRepositoryInterface;
use DateTimeImmutable;
use JsonException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Flush после debounce: собирает сообщения диалога до include_until и отвечает одним запросом к AI.
 */
final class ProcessTelegramMessageJobHandler
{
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
        $includeUntilRaw = $payload['include_until'] ?? null;
        $replyToMessageId = $payload['reply_to_message_id'] ?? null;
        $businessConnectionId = $payload['business_connection_id'] ?? null;

        if (!is_string($channelId) || $channelId === '') {
            throw new RuntimeException('В payload отсутствует channel_id.');
        }

        if (!is_int($chatId) && !is_string($chatId)) {
            throw new RuntimeException('В payload отсутствует chat_id.');
        }

        if (!is_string($includeUntilRaw) || $includeUntilRaw === '') {
            throw new RuntimeException('В payload отсутствует include_until.');
        }

        if (!is_int($replyToMessageId) && !is_string($replyToMessageId)) {
            throw new RuntimeException('В payload отсутствует reply_to_message_id.');
        }

        if ($businessConnectionId !== null && !is_string($businessConnectionId)) {
            $businessConnectionId = null;
        }

        $includeUntil = new DateTimeImmutable($includeUntilRaw);
        $channel = $this->channelRepository->findById($channelId);

        if ($channel === null || $channel->getStatus() !== ChannelStatus::Connected) {
            throw new RuntimeException('Канал не найден или не подключён.');
        }

        $subscription = $this->subscriptionRepository->findActiveByUserId($channel->getUserId());

        if ($subscription === null) {
            throw new RuntimeException('У пользователя нет активной подписки.');
        }

        $user = $this->userRepository->findById($channel->getUserId());

        if ($user === null) {
            throw new RuntimeException('Пользователь канала не найден.');
        }

        $peerKey = (string) $chatId;
        $maxMessagesPerDialog = max(1, $subscription->getMaxMessagesPerDialog());
        $now = new DateTimeImmutable();

        $windowStartedAt = $this->usageRepository->findPeerWindowStartedAt($channel->getUserId(), $peerKey);

        if ($windowStartedAt === null || !DialogBillingRules::isWithinDialogWindow($windowStartedAt, $now)) {
            $windowStartedAt = $now->modify('-' . DialogBillingRules::DIALOG_TTL_SECONDS . ' seconds');

            if ($windowStartedAt === false) {
                $windowStartedAt = new DateTimeImmutable('-24 hours');
            }
        }

        $history = $this->conversationMessageRepository->listSince(
            channelId: $channelId,
            peerKey: $peerKey,
            since: $windowStartedAt,
            until: $includeUntil,
            limit: $maxMessagesPerDialog * 2,
        );

        $hasUnansweredUserMessage = false;

        foreach ($history as $historyMessage) {
            if ($historyMessage->getRole() === ConversationMessageRole::User) {
                $hasUnansweredUserMessage = true;
            } elseif ($historyMessage->getRole() === ConversationMessageRole::Assistant) {
                $hasUnansweredUserMessage = false;
            }
        }

        if (!$hasUnansweredUserMessage) {
            return;
        }

        $systemPrompt = $this->buildSystemPrompt(
            companyName: $user->getCompanyName(),
            description: $user->getDescription(),
            gender: $user->getGender(),
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
            botToken: $this->resolveBotToken($channelId),
            chatId: $chatId,
            text: $aiResult->content,
            businessConnectionId: $businessConnectionId,
            replyToMessageId: (int) $replyToMessageId,
        );
    }

    /**
     * @param list<string> $knowledgeBaseIds
     */
    private function buildSystemPrompt(
        string $companyName,
        string $description,
        ?UserGender $gender,
        array $knowledgeBaseIds,
    ): string {
        $parts = [
            'Ты ИИ-ассистент бизнеса в мессенджере. Отвечай кратко, вежливо и по делу на русском языке.',
            'Не выдумывай факты, которых нет в описании бизнеса и базе знаний.',
        ];

        if ($gender !== null) {
            $parts[] = 'Отвечай от имени пользователя с учетом рода: ' . $gender->label();
        }

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
