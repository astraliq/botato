<?php

declare(strict_types=1);

namespace App\Application\KnowledgeBase\DeleteKnowledgeArticle;

use App\Domain\KnowledgeBase\KnowledgeArticleRepositoryInterface;
use App\Domain\KnowledgeBase\KnowledgeBaseRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class DeleteKnowledgeArticleHandler
{
    public function __construct(
        private KnowledgeBaseRepositoryInterface $knowledgeBaseRepository,
        private KnowledgeArticleRepositoryInterface $knowledgeArticleRepository,
    ) {}

    public function handle(string $userId, string $articleId): string
    {
        $article = $this->knowledgeArticleRepository->findById($articleId);

        if ($article === null) {
            throw new InvalidArgumentException('Статья не найдена.');
        }

        $knowledgeBase = $this->knowledgeBaseRepository->findById($article->getKnowledgeBaseId());

        if ($knowledgeBase === null || $knowledgeBase->getUserId() !== $userId) {
            throw new InvalidArgumentException('Статья не найдена.');
        }

        $article->markDeleted(new DateTimeImmutable());
        $this->knowledgeArticleRepository->save($article);

        return $knowledgeBase->getId();
    }
}
