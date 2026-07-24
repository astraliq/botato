<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

return [
    'up' => static function (ConnectionInterface $connection): void {
        $connection->createCommand(
            'ALTER TABLE plans
             ADD COLUMN IF NOT EXISTS messages_per_dialog INT NOT NULL DEFAULT 10',
        )->execute();

        $connection->createCommand(
            'ALTER TABLE subscriptions
             ADD COLUMN IF NOT EXISTS messages_per_dialog INT NOT NULL DEFAULT 10',
        )->execute();

        $connection->createCommand(
            'UPDATE plans SET messages_per_dialog = 10 WHERE messages_per_dialog IS NULL OR messages_per_dialog < 1',
        )->execute();

        $connection->createCommand(
            'UPDATE subscriptions s
             SET messages_per_dialog = p.messages_per_dialog
             FROM plans p
             WHERE s.plan_id = p.id',
        )->execute();

        $connection->createCommand(
            'ALTER TABLE plans
             ALTER COLUMN messages_per_dialog DROP DEFAULT',
        )->execute();

        $connection->createCommand(
            'ALTER TABLE subscriptions
             ALTER COLUMN messages_per_dialog DROP DEFAULT',
        )->execute();
    },
];
