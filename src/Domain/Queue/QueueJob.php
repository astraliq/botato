<?php

declare(strict_types=1);

namespace App\Domain\Queue;

use DateTimeImmutable;

final class QueueJob
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly string $id,
        private readonly string $queue,
        private readonly string $jobType,
        private readonly array $payload,
        private readonly ?string $dedupeKey,
        private QueueJobStatus $status,
        private int $attempts,
        private readonly int $maxAttempts,
        private DateTimeImmutable $availableAt,
        private ?DateTimeImmutable $reservedAt,
        private ?DateTimeImmutable $processedAt,
        private ?string $lastError,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getQueue(): string
    {
        return $this->queue;
    }

    public function getJobType(): string
    {
        return $this->jobType;
    }

    /** @return array<string, mixed> */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getDedupeKey(): ?string
    {
        return $this->dedupeKey;
    }

    public function getStatus(): QueueJobStatus
    {
        return $this->status;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function getAvailableAt(): DateTimeImmutable
    {
        return $this->availableAt;
    }

    public function getReservedAt(): ?DateTimeImmutable
    {
        return $this->reservedAt;
    }

    public function getProcessedAt(): ?DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function hasAttemptsLeft(): bool
    {
        return $this->attempts < $this->maxAttempts;
    }
}
