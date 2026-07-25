<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

return [
    'up' => static function (ConnectionInterface $connection): void {
        $connection->createCommand(
            'ALTER TABLE plans
             RENAME COLUMN messages_per_dialog TO max_messages_per_dialog',
        )->execute();

        $connection->createCommand(
            'ALTER TABLE subscriptions
             RENAME COLUMN messages_per_dialog TO max_messages_per_dialog',
        )->execute();
    },
];
