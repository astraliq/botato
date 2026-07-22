<?php

declare(strict_types=1);

namespace App\Domain\KnowledgeBase;

use DateTimeImmutable;

final class KnowledgeArticle
{
    public function __construct(
        private readonly string $id,
        private readonly string $knowledgeBaseId,
        private string $title,
        private string $content,
        private int $sortOrder,
        private bool $isActive,
        private bool $deleted,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        string $id,
        string $knowledgeBaseId,
        string $title,
        string $content,
        DateTimeImmutable $createdAt,
        int $sortOrder = 0,
    ): self {
        return new self(
            id: $id,
            knowledgeBaseId: $knowledgeBaseId,
            title: $title,
            content: $content,
            sortOrder: $sortOrder,
            isActive: true,
            deleted: false,
            createdAt: $createdAt,
            updatedAt: $createdAt,
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getKnowledgeBaseId(): string
    {
        return $this->knowledgeBaseId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function update(
        string $title,
        string $content,
        int $sortOrder,
        bool $isActive,
        DateTimeImmutable $updatedAt,
    ): void {
        $this->title = $title;
        $this->content = $content;
        $this->sortOrder = $sortOrder;
        $this->isActive = $isActive;
        $this->updatedAt = $updatedAt;
    }

    public function markDeleted(DateTimeImmutable $updatedAt): void
    {
        $this->deleted = true;
        $this->updatedAt = $updatedAt;
    }
}
