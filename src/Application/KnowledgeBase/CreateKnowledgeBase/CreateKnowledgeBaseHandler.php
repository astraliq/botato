<?php

declare(strict_types=1);

namespace App\Application\KnowledgeBase\CreateKnowledgeBase;

use App\Domain\Channel\ChannelProvider;
use App\Domain\Channel\ChannelRepositoryInterface;
use App\Domain\KnowledgeBase\KnowledgeBase;
use App\Domain\KnowledgeBase\KnowledgeBaseRepositoryInterface;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class CreateKnowledgeBaseHandler
{
    public function __construct(
        private readonly KnowledgeBaseRepositoryInterface $knowledgeBaseRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly ChannelRepositoryInterface $channelRepository,
    ) {}

    public function handle(string $userId, string $title, string $summary = ''): KnowledgeBase
    {
        $title = trim($title);
        $summary = trim($summary);

        if ($title === '') {
            throw new InvalidArgumentException('Укажите название базы знаний.');
        }

        if (mb_strlen($title) > 255) {
            throw new InvalidArgumentException('Название не должно превышать 255 символов.');
        }

        if (mb_strlen($summary) > 2000) {
            throw new InvalidArgumentException('Описание не должно превышать 2000 символов.');
        }

        $subscription = $this->subscriptionRepository->findActiveByUserId($userId);

        if ($subscription === null) {
            throw new InvalidArgumentException('Нет активной подписки.');
        }

        $currentCount = $this->knowledgeBaseRepository->countByUserId($userId);

        if ($currentCount >= $subscription->getMaxKnowledgeBases()) {
            throw new InvalidArgumentException(
                'Достигнут лимит баз знаний для вашего тарифа (' . $subscription->getMaxKnowledgeBases() . ').',
            );
        }

        $now = new DateTimeImmutable();
        $knowledgeBase = KnowledgeBase::create(
            id: Uuid::uuid4()->toString(),
            userId: $userId,
            title: $title,
            createdAt: $now,
            summary: $summary,
        );

        $this->knowledgeBaseRepository->save($knowledgeBase);

        $telegramChannel = $this->channelRepository->findConnectedByUserAndProvider(
            $userId,
            ChannelProvider::Telegram,
        );

        if ($telegramChannel !== null && $telegramChannel->getKnowledgeBaseId() === null) {
            $telegramChannel->assignKnowledgeBase($knowledgeBase->getId(), $now);
            $this->channelRepository->save($telegramChannel);
        }

        return $knowledgeBase;
    }
}
