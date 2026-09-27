<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Queue\QueueJob;
use App\Domain\Queue\QueueJobRepositoryInterface;
use App\Domain\Queue\QueueJobStatus;
use App\Shared\ArrayHelper;
use DateTimeImmutable;
use JsonException;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class QueueJobRepository implements QueueJobRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {}

    public function enqueue(
        string $queue,
        string $jobType,
        array $payload,
        ?string $dedupeKey = null,
        int $maxAttempts = 5,
        ?DateTimeImmutable $availableAt = null,
    ): ?QueueJob {
        $now = new DateTimeImmutable();
        $availableAt ??= $now;
        $id = Uuid::uuid4()->toString();

        try {
            $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $exception) {
            throw new RuntimeException('Не удалось сериализовать payload очереди.', previous: $exception);
        }

        $inserted = $this->connection
            ->createCommand(
                'INSERT INTO queue_jobs (
                    id, queue, job_type, payload, dedupe_key, status,
                    attempts, max_attempts, available_at, created_at
                 ) VALUES (
                    :id, :queue, :job_type, CAST(:payload AS JSONB), :dedupe_key, :status,
                    0, :max_attempts, :available_at, :created_at
                 )
                 ON CONFLICT (dedupe_key) WHERE dedupe_key IS NOT NULL AND status = \'pending\' DO NOTHING',
            )
            ->bindValues([
                ':id' => $id,
                ':queue' => $queue,
                ':job_type' => $jobType,
                ':payload' => $payloadJson,
                ':dedupe_key' => $dedupeKey,
                ':status' => QueueJobStatus::Pending->value,
                ':max_attempts' => $maxAttempts,
                ':available_at' => $availableAt->format('Y-m-d H:i:sP'),
                ':created_at' => $now->format('Y-m-d H:i:sP'),
            ])
            ->execute();

        if ($inserted === 0) {
            return null;
        }

        $row = $this->connection
            ->createCommand('SELECT * FROM queue_jobs WHERE id = :id')
            ->bindValue(':id', $id)
            ->queryOne();

        if (!is_array($row)) {
            throw new RuntimeException('Не удалось прочитать созданную задачу очереди.');
        }

        return QueueJobFactory::fromRow(ArrayHelper::toStringKeyArray($row));
    }

    public function scheduleOrPostpone(
        string $queue,
        string $jobType,
        array $payload,
        string $dedupeKey,
        DateTimeImmutable $availableAt,
        int $maxAttempts = 5,
    ): void {
        try {
            $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $exception) {
            throw new RuntimeException('Не удалось сериализовать payload очереди.', previous: $exception);
        }

        $updated = $this->connection
            ->createCommand(
                'UPDATE queue_jobs SET
                    available_at = :available_at,
                    payload = CAST(:payload AS JSONB),
                    last_error = NULL
                 WHERE dedupe_key = :dedupe_key
                   AND status = :pending',
            )
            ->bindValues([
                ':available_at' => $availableAt->format('Y-m-d H:i:sP'),
                ':payload' => $payloadJson,
                ':dedupe_key' => $dedupeKey,
                ':pending' => QueueJobStatus::Pending->value,
            ])
            ->execute();

        if ($updated > 0) {
            return;
        }

        $job = $this->enqueue(
            queue: $queue,
            jobType: $jobType,
            payload: $payload,
            dedupeKey: $dedupeKey,
            maxAttempts: $maxAttempts,
            availableAt: $availableAt,
        );

        if ($job !== null) {
            return;
        }

        // Гонка: pending уже появился — ещё раз сдвигаем.
        $this->connection
            ->createCommand(
                'UPDATE queue_jobs SET
                    available_at = :available_at,
                    payload = CAST(:payload AS JSONB),
                    last_error = NULL
                 WHERE dedupe_key = :dedupe_key
                   AND status = :pending',
            )
            ->bindValues([
                ':available_at' => $availableAt->format('Y-m-d H:i:sP'),
                ':payload' => $payloadJson,
                ':dedupe_key' => $dedupeKey,
                ':pending' => QueueJobStatus::Pending->value,
            ])
            ->execute();
    }

    public function claimNext(string $queue = 'default'): ?QueueJob
    {
        $row = $this->connection
            ->createCommand(
                'UPDATE queue_jobs SET
                    status = :processing,
                    reserved_at = NOW(),
                    attempts = attempts + 1
                 WHERE id = (
                    SELECT id FROM queue_jobs
                    WHERE queue = :queue
                      AND status = :pending
                      AND available_at <= NOW()
                    ORDER BY available_at ASC
                    FOR UPDATE SKIP LOCKED
                    LIMIT 1
                 )
                 RETURNING *',
            )
            ->bindValues([
                ':processing' => QueueJobStatus::Processing->value,
                ':queue' => $queue,
                ':pending' => QueueJobStatus::Pending->value,
            ])
            ->queryOne();

        return is_array($row) ? QueueJobFactory::fromRow(ArrayHelper::toStringKeyArray($row)) : null;
    }

    public function markDone(string $jobId): void
    {
        $this->connection
            ->createCommand(
                'UPDATE queue_jobs SET
                    status = :status,
                    processed_at = NOW(),
                    last_error = NULL
                 WHERE id = :id',
            )
            ->bindValues([
                ':status' => QueueJobStatus::Done->value,
                ':id' => $jobId,
            ])
            ->execute();
    }

    public function markFailed(
        string $jobId,
        string $error,
        DateTimeImmutable $availableAt,
        bool $permanently,
    ): void {
        if ($permanently) {
            $this->connection
                ->createCommand(
                    'UPDATE queue_jobs SET
                        status = :status,
                        available_at = :available_at,
                        last_error = :last_error,
                        processed_at = NOW(),
                        reserved_at = NULL
                     WHERE id = :id',
                )
                ->bindValues([
                    ':status' => QueueJobStatus::Failed->value,
                    ':available_at' => $availableAt->format('Y-m-d H:i:sP'),
                    ':last_error' => mb_substr($error, 0, 4000),
                    ':id' => $jobId,
                ])
                ->execute();

            return;
        }

        $this->connection
            ->createCommand(
                'UPDATE queue_jobs SET
                    status = :status,
                    available_at = :available_at,
                    last_error = :last_error,
                    reserved_at = NULL
                 WHERE id = :id',
            )
            ->bindValues([
                ':status' => QueueJobStatus::Pending->value,
                ':available_at' => $availableAt->format('Y-m-d H:i:sP'),
                ':last_error' => mb_substr($error, 0, 4000),
                ':id' => $jobId,
            ])
            ->execute();
    }
}
