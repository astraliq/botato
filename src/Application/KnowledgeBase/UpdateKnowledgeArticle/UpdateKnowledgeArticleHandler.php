<?php

declare(strict_types=1);

namespace App\Application\KnowledgeBase\UpdateKnowledgeArticle;

use App\Domain\KnowledgeBase\KnowledgeArticle;
use App\Domain\KnowledgeBase\KnowledgeArticleRepositoryInterface;
use App\Domain\KnowledgeBase\KnowledgeBaseRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final class UpdateKnowledgeArticleHandler
{
    public function __construct(
        private readonly KnowledgeBaseRepositoryInterface $knowledgeBaseRepository,
        private readonly KnowledgeArticleRepositoryInterface $knowledgeArticleRepository,
    ) {}

    public function handle(
        string $userId,
        string $articleId,
        string $title,
        string $content,
        bool $isActive = true,
    ): KnowledgeArticle {
        $article = $this->requireOwnedArticle($userId, $articleId);

        $title = trim($title);
        $content = trim($content);

        if ($title === '') {
            throw new InvalidArgumentException('Укажите заголовок статьи.');
        }

        if ($content === '') {
            throw new InvalidArgumentException('Укажите текст статьи.');
        }

        if (mb_strlen($title) > 255) {
            throw new InvalidArgumentException('Заголовок не должен превышать 255 символов.');
        }

        if (mb_strlen($content) > 50000) {
            throw new InvalidArgumentException('Текст статьи не должен превышать 50000 символов.');
        }

        $article->update(
            title: $title,
            content: $content,
            sortOrder: $article->getSortOrder(),
            isActive: $isActive,
            updatedAt: new DateTimeImmutable(),
        );

        $this->knowledgeArticleRepository->save($article);

        return $article;
    }

    private function requireOwnedArticle(string $userId, string $articleId): KnowledgeArticle
    {
        $article = $this->knowledgeArticleRepository->findById($articleId);

        if ($article === null) {
            throw new InvalidArgumentException('Статья не найдена.');
        }

        $knowledgeBase = $this->knowledgeBaseRepository->findById($article->getKnowledgeBaseId());

        if ($knowledgeBase === null || $knowledgeBase->getUserId() !== $userId) {
            throw new InvalidArgumentException('Статья не найдена.');
        }

        return $article;
    }
}
