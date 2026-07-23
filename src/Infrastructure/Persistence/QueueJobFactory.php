<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Queue\QueueJob;
use App\Domain\Queue\QueueJobStatus;
use App\Shared\ArrayHelper;
use DateTimeImmutable;

final class QueueJobFactory
{
    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): QueueJob
    {
        $payload = self::decodePayload($row['payload'] ?? []);

        return new QueueJob(
            id: (string) $row['id'],
            queue: (string) $row['queue'],
            jobType: (string) $row['job_type'],
            payload: $payload,
            dedupeKey: isset($row['dedupe_key']) && $row['dedupe_key'] !== null && $row['dedupe_key'] !== ''
                ? (string) $row['dedupe_key']
                : null,
            status: QueueJobStatus::from((string) $row['status']),
            attempts: (int) $row['attempts'],
            maxAttempts: (int) $row['max_attempts'],
            availableAt: new DateTimeImmutable((string) $row['available_at']),
            reservedAt: isset($row['reserved_at']) && $row['reserved_at'] !== null
                ? new DateTimeImmutable((string) $row['reserved_at'])
                : null,
            processedAt: isset($row['processed_at']) && $row['processed_at'] !== null
                ? new DateTimeImmutable((string) $row['processed_at'])
                : null,
            lastError: isset($row['last_error']) && $row['last_error'] !== null && $row['last_error'] !== ''
                ? (string) $row['last_error']
                : null,
            createdAt: new DateTimeImmutable((string) $row['created_at']),
        );
    }

    /** @return array<string, mixed> */
    private static function decodePayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return ArrayHelper::toStringKeyArray($payload);
        }

        if (is_string($payload) && $payload !== '') {
            $decoded = json_decode($payload, true);

            return is_array($decoded) ? ArrayHelper::toStringKeyArray($decoded) : [];
        }

        return [];
    }
}
