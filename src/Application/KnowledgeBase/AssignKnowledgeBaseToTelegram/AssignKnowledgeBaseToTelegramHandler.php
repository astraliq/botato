<?php

declare(strict_types=1);

namespace App\Application\KnowledgeBase\AssignKnowledgeBaseToTelegram;

use App\Domain\Channel\ChannelProvider;
use App\Domain\Channel\ChannelRepositoryInterface;
use App\Domain\KnowledgeBase\KnowledgeBaseRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final class AssignKnowledgeBaseToTelegramHandler
{
    public function __construct(
        private readonly KnowledgeBaseRepositoryInterface $knowledgeBaseRepository,
        private readonly ChannelRepositoryInterface $channelRepository,
    ) {}

    public function handle(string $userId, string $knowledgeBaseId): void
    {
        $knowledgeBase = $this->knowledgeBaseRepository->findById($knowledgeBaseId);

        if ($knowledgeBase === null || $knowledgeBase->getUserId() !== $userId) {
            throw new InvalidArgumentException('База знаний не найдена.');
        }

        $telegramChannel = $this->channelRepository->findConnectedByUserAndProvider(
            $userId,
            ChannelProvider::Telegram,
        );

        if ($telegramChannel === null) {
            throw new InvalidArgumentException('Сначала подключите Telegram-бота.');
        }

        $telegramChannel->assignKnowledgeBase($knowledgeBaseId, new DateTimeImmutable());
        $this->channelRepository->save($telegramChannel);
    }
}
