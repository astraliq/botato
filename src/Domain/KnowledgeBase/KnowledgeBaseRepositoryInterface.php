<?php

declare(strict_types=1);

namespace App\Domain\KnowledgeBase;

interface KnowledgeBaseRepositoryInterface
{
    public function findById(string $id): ?KnowledgeBase;

    /** @return list<KnowledgeBase> */
    public function listByUserId(string $userId): array;

    public function countByUserId(string $userId): int;

    public function save(KnowledgeBase $knowledgeBase): void;
}
