<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

return [
    'up' => static function (ConnectionInterface $connection): void {
        $connection->createCommand(
            'ALTER TABLE users
             ADD COLUMN IF NOT EXISTS is_admin BOOLEAN NOT NULL DEFAULT FALSE',
        )->execute();
    },
];
