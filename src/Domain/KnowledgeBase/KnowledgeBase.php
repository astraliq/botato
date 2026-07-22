<?php

declare(strict_types=1);

namespace App\Domain\KnowledgeBase;

use DateTimeImmutable;

final class KnowledgeBase
{
    public function __construct(
        private readonly string $id,
        private readonly string $userId,
        private string $title,
        private string $summary,
        private bool $isActive,
        private bool $deleted,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        string $id,
        string $userId,
        string $title,
        DateTimeImmutable $createdAt,
        string $summary = '',
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            title: $title,
            summary: $summary,
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

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSummary(): string
    {
        return $this->summary;
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

    public function update(string $title, string $summary, bool $isActive, DateTimeImmutable $updatedAt): void
    {
        $this->title = $title;
        $this->summary = $summary;
        $this->isActive = $isActive;
        $this->updatedAt = $updatedAt;
    }

    public function markDeleted(DateTimeImmutable $updatedAt): void
    {
        $this->deleted = true;
        $this->updatedAt = $updatedAt;
    }
}
