<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

return [
    'up' => static function (ConnectionInterface $connection): void {
        $connection->createCommand(
            'ALTER TABLE plans
             ADD COLUMN IF NOT EXISTS deleted BOOLEAN NOT NULL DEFAULT FALSE',
        )->execute();
    },
];
