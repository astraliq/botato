<?php

declare(strict_types=1);

namespace App\Domain\Support;

use DateTimeImmutable;

final class SupportTicket
{
    public function __construct(
        private readonly string $id,
        private readonly string $userId,
        private readonly string $question,
        private bool $isClosed,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        string $id,
        string $userId,
        string $question,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            question: $question,
            isClosed: false,
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

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function isClosed(): bool
    {
        return $this->isClosed;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function close(DateTimeImmutable $updatedAt): void
    {
        $this->isClosed = true;
        $this->updatedAt = $updatedAt;
    }
}
