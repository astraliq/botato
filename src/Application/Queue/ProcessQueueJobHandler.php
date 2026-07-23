<?php

declare(strict_types=1);

namespace App\Application\Queue;

use App\Application\Channel\Telegram\ProcessMessage\ProcessTelegramMessageJobHandler;
use App\Domain\Queue\QueueJob;
use App\Domain\Queue\QueueJobRepositoryInterface;
use App\Domain\Queue\QueueJobType;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class ProcessQueueJobHandler
{
    public function __construct(
        private readonly QueueJobRepositoryInterface $queueJobRepository,
        private readonly ProcessTelegramMessageJobHandler $processTelegramMessageJobHandler,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(QueueJob $job): void
    {
        try {
            match ($job->getJobType()) {
                QueueJobType::TELEGRAM_PROCESS_MESSAGE => $this->processTelegramMessageJobHandler->handle(
                    $job->getPayload(),
                ),
                default => throw new RuntimeException('Неизвестный тип задачи: ' . $job->getJobType()),
            };

            $this->queueJobRepository->markDone($job->getId());
        } catch (Throwable $exception) {
            $this->logger->error('Queue job failed.', [
                'job_id' => $job->getId(),
                'job_type' => $job->getJobType(),
                'attempts' => $job->getAttempts(),
                'exception' => $exception->getMessage(),
            ]);

            $permanently = !$job->hasAttemptsLeft();
            $delaySeconds = min(300, 2 ** max(0, $job->getAttempts() - 1));
            $availableAt = (new DateTimeImmutable())->modify('+' . $delaySeconds . ' seconds');

            if ($availableAt === false) {
                $availableAt = new DateTimeImmutable('+60 seconds');
            }

            $this->queueJobRepository->markFailed(
                jobId: $job->getId(),
                error: $exception->getMessage(),
                availableAt: $availableAt,
                permanently: $permanently,
            );

            if ($permanently) {
                throw $exception;
            }
        }
    }
}
