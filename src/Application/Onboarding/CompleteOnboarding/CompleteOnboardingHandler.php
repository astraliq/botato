<?php

declare(strict_types=1);

namespace App\Application\Onboarding\CompleteOnboarding;

use App\Domain\Channel\ChannelProvider;
use App\Domain\Channel\ChannelRepositoryInterface;
use App\Domain\Channel\ChannelStatus;
use App\Domain\KnowledgeBase\KnowledgeBaseRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class CompleteOnboardingHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private KnowledgeBaseRepositoryInterface $knowledgeBaseRepository,
        private ChannelRepositoryInterface $channelRepository,
    ) {}

    /**
     * @param list<string> $knowledgeBaseIds
     */
    public function handle(string $userId, array $knowledgeBaseIds = []): void
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null || $user->isDeleted()) {
            throw new InvalidArgumentException('Пользователь не найден.');
        }

        if ($user->isOnboardingCompleted()) {
            return;
        }

        if ($user->getName() === '' || $user->getSurname() === '' || $user->getCompanyName() === '' || $user->getGender() === null) {
            throw new InvalidArgumentException('Сначала заполните профиль.');
        }

        $knowledgeBases = $this->knowledgeBaseRepository->listByUserId($userId);

        if ($knowledgeBases === []) {
            throw new InvalidArgumentException('Создайте хотя бы одну базу знаний.');
        }

        $telegramChannel = $this->channelRepository->findConnectedByUserAndProvider(
            $userId,
            ChannelProvider::Telegram,
        );

        if ($telegramChannel === null || $telegramChannel->getStatus() !== ChannelStatus::Connected) {
            throw new InvalidArgumentException('Подключите Telegram-канал.');
        }

        $ownedIds = [];

        foreach ($knowledgeBases as $knowledgeBase) {
            $ownedIds[$knowledgeBase->getId()] = true;
        }

        $selectedIds = [];

        foreach ($knowledgeBaseIds as $knowledgeBaseId) {
            if ($knowledgeBaseId === '') {
                continue;
            }

            if (!isset($ownedIds[$knowledgeBaseId])) {
                throw new InvalidArgumentException('Выбрана чужая или несуществующая база знаний.');
            }

            $selectedIds[] = $knowledgeBaseId;
        }

        if ($selectedIds === []) {
            $selectedIds = [$knowledgeBases[0]->getId()];
        }

        $now = new DateTimeImmutable();
        $telegramChannel->assignKnowledgeBases($selectedIds, $now);
        $this->channelRepository->save($telegramChannel);

        $user->markOnboardingCompleted($now, $now);
        $this->userRepository->save($user);
    }
}
