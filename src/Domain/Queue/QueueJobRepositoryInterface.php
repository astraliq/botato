<?php

declare(strict_types=1);

namespace App\Domain\Queue;

use DateTimeImmutable;

interface QueueJobRepositoryInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return QueueJob|null null если dedupe_key уже есть
     */
    public function enqueue(
        string $queue,
        string $jobType,
        array $payload,
        ?string $dedupeKey = null,
        int $maxAttempts = 5,
        ?DateTimeImmutable $availableAt = null,
    ): ?QueueJob;

    /**
     * Создаёт pending-задачу или сдвигает available_at / обновляет payload у существующей pending с тем же dedupe_key.
     *
     * @param array<string, mixed> $payload
     */
    public function scheduleOrPostpone(
        string $queue,
        string $jobType,
        array $payload,
        string $dedupeKey,
        DateTimeImmutable $availableAt,
        int $maxAttempts = 5,
    ): void;

    public function claimNext(string $queue = 'default'): ?QueueJob;

    public function markDone(string $jobId): void;

    public function markFailed(string $jobId, string $error, DateTimeImmutable $availableAt, bool $permanently): void;
}
