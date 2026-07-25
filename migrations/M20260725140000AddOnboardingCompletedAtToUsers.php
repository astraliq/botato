<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

return [
    'up' => static function (ConnectionInterface $connection): void {
        $connection->createCommand(
            'ALTER TABLE users
             ADD COLUMN IF NOT EXISTS onboarding_completed_at TIMESTAMPTZ NULL',
        )->execute();

        // Уже настроенные аккаунты не гоняем в визард; пустой профиль — в онбординг.
        $connection->createCommand(
            'UPDATE users
             SET onboarding_completed_at = COALESCE(updated_at, created_at)
             WHERE onboarding_completed_at IS NULL
               AND deleted = FALSE
               AND name <> \'\'
               AND surname <> \'\'
               AND company_name <> \'\'
               AND gender IS NOT NULL',
        )->execute();
    },
];
