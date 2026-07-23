<?php

declare(strict_types=1);

namespace App\Domain\KnowledgeBase;

interface KnowledgeArticleRepositoryInterface
{
    public function findById(string $id): ?KnowledgeArticle;

    /** @return list<KnowledgeArticle> */
    public function listByKnowledgeBaseId(string $knowledgeBaseId): array;

    /**
     * Статьи для промпта: deleted = false AND is_active = true.
     *
     * @return list<KnowledgeArticle>
     */
    public function listActiveForPrompt(string $knowledgeBaseId): array;

    /**
     * @param list<string> $knowledgeBaseIds
     * @return list<KnowledgeArticle>
     */
    public function listActiveForPromptByKnowledgeBaseIds(array $knowledgeBaseIds): array;

    public function save(KnowledgeArticle $article): void;
}
