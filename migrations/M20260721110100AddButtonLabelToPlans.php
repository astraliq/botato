<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

return [
    'up' => static function (ConnectionInterface $connection): void {
        $connection->createCommand(
            "ALTER TABLE plans
             ADD COLUMN IF NOT EXISTS button_label VARCHAR(64) NOT NULL DEFAULT 'Подключить'",
        )->execute();

        $connection->createCommand(
            "UPDATE plans SET button_label = 'Начать бесплатно' WHERE code = 'free'",
        )->execute();

        $connection->createCommand(
            "UPDATE plans SET button_label = 'Подключить' WHERE code <> 'free'",
        )->execute();
    },
];
