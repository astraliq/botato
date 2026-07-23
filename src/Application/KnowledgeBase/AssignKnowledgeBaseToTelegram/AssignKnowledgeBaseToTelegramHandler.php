<?php

declare(strict_types=1);

namespace App\Application\KnowledgeBase\AssignKnowledgeBaseToTelegram;

use App\Domain\Channel\ChannelProvider;
use App\Domain\Channel\ChannelRepositoryInterface;
use App\Domain\KnowledgeBase\KnowledgeBaseRepositoryInterface;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final class AssignKnowledgeBaseToTelegramHandler
{
    public function __construct(
        private readonly KnowledgeBaseRepositoryInterface $knowledgeBaseRepository,
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
    ) {}

    /**
     * @param list<string> $knowledgeBaseIds
     * @return list<string>
     */
    public function handle(string $userId, array $knowledgeBaseIds): array
    {
        $telegramChannel = $this->channelRepository->findConnectedByUserAndProvider(
            $userId,
            ChannelProvider::Telegram,
        );

        if ($telegramChannel === null) {
            throw new InvalidArgumentException('Сначала подключите Telegram-бота.');
        }

        $subscription = $this->subscriptionRepository->findActiveByUserId($userId);

        if ($subscription === null) {
            throw new InvalidArgumentException('Нет активной подписки.');
        }

        $normalizedIds = [];

        foreach ($knowledgeBaseIds as $knowledgeBaseId) {
            if ($knowledgeBaseId === '') {
                continue;
            }

            if (in_array($knowledgeBaseId, $normalizedIds, true)) {
                continue;
            }

            $knowledgeBase = $this->knowledgeBaseRepository->findById($knowledgeBaseId);

            if ($knowledgeBase === null || $knowledgeBase->getUserId() !== $userId) {
                throw new InvalidArgumentException('База знаний не найдена.');
            }

            if (!$knowledgeBase->isActive()) {
                throw new InvalidArgumentException(
                    'База «' . $knowledgeBase->getTitle() . '» неактивна и не может быть привязана.',
                );
            }

            $normalizedIds[] = $knowledgeBaseId;
        }

        $maxKnowledgeBases = $subscription->getMaxKnowledgeBases();

        if (count($normalizedIds) > $maxKnowledgeBases) {
            throw new InvalidArgumentException(
                'По тарифу можно привязать не больше ' . $maxKnowledgeBases . ' '
                . $this->pluralBases($maxKnowledgeBases) . '.',
            );
        }

        $telegramChannel->assignKnowledgeBases($normalizedIds, new DateTimeImmutable());
        $this->channelRepository->save($telegramChannel);

        return $normalizedIds;
    }

    private function pluralBases(int $count): string
    {
        $mod100 = $count % 100;
        $mod10 = $count % 10;

        if ($mod100 >= 11 && $mod100 <= 14) {
            return 'баз знаний';
        }

        if ($mod10 === 1) {
            return 'базы знаний';
        }

        if ($mod10 >= 2 && $mod10 <= 4) {
            return 'баз знаний';
        }

        return 'баз знаний';
    }
}
