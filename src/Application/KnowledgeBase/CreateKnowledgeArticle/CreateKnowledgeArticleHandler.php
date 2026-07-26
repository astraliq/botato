<?php

declare(strict_types=1);

namespace App\Application\KnowledgeBase\CreateKnowledgeArticle;

use App\Domain\KnowledgeBase\KnowledgeArticle;
use App\Domain\KnowledgeBase\KnowledgeArticleRepositoryInterface;
use App\Domain\KnowledgeBase\KnowledgeBaseRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final readonly class CreateKnowledgeArticleHandler
{
    public function __construct(
        private KnowledgeBaseRepositoryInterface $knowledgeBaseRepository,
        private KnowledgeArticleRepositoryInterface $knowledgeArticleRepository,
    ) {}

    public function handle(
        string $userId,
        string $knowledgeBaseId,
        string $title,
        string $content,
    ): KnowledgeArticle {
        $this->requireOwnedKnowledgeBase($userId, $knowledgeBaseId);

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

        $existingArticles = $this->knowledgeArticleRepository->listByKnowledgeBaseId($knowledgeBaseId);
        $sortOrder = count($existingArticles);

        $article = KnowledgeArticle::create(
            id: Uuid::uuid4()->toString(),
            knowledgeBaseId: $knowledgeBaseId,
            title: $title,
            content: $content,
            createdAt: new DateTimeImmutable(),
            sortOrder: $sortOrder,
        );

        $this->knowledgeArticleRepository->save($article);

        return $article;
    }

    private function requireOwnedKnowledgeBase(string $userId, string $knowledgeBaseId): void
    {
        $knowledgeBase = $this->knowledgeBaseRepository->findById($knowledgeBaseId);

        if ($knowledgeBase === null || $knowledgeBase->getUserId() !== $userId) {
            throw new InvalidArgumentException('База знаний не найдена.');
        }
    }
}
