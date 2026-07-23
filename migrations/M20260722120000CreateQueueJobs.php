<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

return [
    'up' => static function (ConnectionInterface $connection): void {
        $connection->createCommand(
            'CREATE TABLE IF NOT EXISTS queue_jobs (
                id UUID PRIMARY KEY,
                queue VARCHAR(64) NOT NULL DEFAULT \'default\',
                job_type VARCHAR(128) NOT NULL,
                payload JSONB NOT NULL,
                dedupe_key VARCHAR(255) NULL,
                status VARCHAR(16) NOT NULL DEFAULT \'pending\',
                attempts INT NOT NULL DEFAULT 0,
                max_attempts INT NOT NULL DEFAULT 5,
                available_at TIMESTAMPTZ NOT NULL,
                reserved_at TIMESTAMPTZ NULL,
                processed_at TIMESTAMPTZ NULL,
                last_error TEXT NULL,
                created_at TIMESTAMPTZ NOT NULL
            )',
        )->execute();

        $connection->createCommand(
            'CREATE UNIQUE INDEX IF NOT EXISTS uq_queue_jobs_dedupe_key
             ON queue_jobs (dedupe_key)
             WHERE dedupe_key IS NOT NULL',
        )->execute();

        $connection->createCommand(
            'CREATE INDEX IF NOT EXISTS idx_queue_jobs_pending
             ON queue_jobs (queue, available_at)
             WHERE status = \'pending\'',
        )->execute();
    },
];
