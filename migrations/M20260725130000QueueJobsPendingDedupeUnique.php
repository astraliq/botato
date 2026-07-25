<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

return [
    'up' => static function (ConnectionInterface $connection): void {
        $connection->createCommand(
            'DROP INDEX IF EXISTS uq_queue_jobs_dedupe_key',
        )->execute();

        // Один pending-job на dedupe_key: можно переносить available_at и создавать новый после processing/done.
        $connection->createCommand(
            'CREATE UNIQUE INDEX IF NOT EXISTS uq_queue_jobs_dedupe_key_pending
             ON queue_jobs (dedupe_key)
             WHERE dedupe_key IS NOT NULL AND status = \'pending\'',
        )->execute();
    },
];
