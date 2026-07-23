<?php

declare(strict_types=1);

namespace App\Application\KnowledgeBase\UpdateKnowledgeBase;

use App\Domain\KnowledgeBase\KnowledgeBase;
use App\Domain\KnowledgeBase\KnowledgeBaseRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final class UpdateKnowledgeBaseHandler
{
    public function __construct(
        private readonly KnowledgeBaseRepositoryInterface $knowledgeBaseRepository,
    ) {}

    public function handle(
        string $userId,
        string $knowledgeBaseId,
        string $title,
        string $summary = '',
        bool $isActive = true,
    ): KnowledgeBase {
        $knowledgeBase = $this->requireOwned($userId, $knowledgeBaseId);

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

        $knowledgeBase->update($title, $summary, $isActive, new DateTimeImmutable());
        $this->knowledgeBaseRepository->save($knowledgeBase);

        return $knowledgeBase;
    }

    private function requireOwned(string $userId, string $knowledgeBaseId): KnowledgeBase
    {
        $knowledgeBase = $this->knowledgeBaseRepository->findById($knowledgeBaseId);

        if ($knowledgeBase === null || $knowledgeBase->getUserId() !== $userId) {
            throw new InvalidArgumentException('База знаний не найдена.');
        }

        return $knowledgeBase;
    }
}
