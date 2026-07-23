<?php

declare(strict_types=1);

namespace App\Application\KnowledgeBase\DeleteKnowledgeBase;

use App\Domain\Channel\ChannelRepositoryInterface;
use App\Domain\KnowledgeBase\KnowledgeBaseRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final class DeleteKnowledgeBaseHandler
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

        $now = new DateTimeImmutable();
        $knowledgeBase->markDeleted($now);
        $this->knowledgeBaseRepository->save($knowledgeBase);

        foreach ($this->channelRepository->listByUserId($userId) as $channel) {
            if (!$channel->hasKnowledgeBase($knowledgeBaseId)) {
                continue;
            }

            $remainingIds = array_values(array_filter(
                $channel->getKnowledgeBaseIds(),
                static fn (string $id): bool => $id !== $knowledgeBaseId,
            ));
            $channel->assignKnowledgeBases($remainingIds, $now);
            $this->channelRepository->save($channel);
        }
    }
}
