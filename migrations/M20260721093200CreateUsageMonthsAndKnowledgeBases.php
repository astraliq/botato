<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

return [
    'up' => static function (ConnectionInterface $connection): void {
        $connection->createCommand(
            'CREATE TABLE IF NOT EXISTS usage_months (
                id UUID PRIMARY KEY,
                user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                period_month DATE NOT NULL,
                dialogs_count INT NOT NULL DEFAULT 0,
                updated_at TIMESTAMPTZ NOT NULL,
                CONSTRAINT uq_usage_months_user_period UNIQUE (user_id, period_month)
            )',
        )->execute();

        $connection->createCommand(
            'CREATE INDEX IF NOT EXISTS idx_usage_months_user_id ON usage_months (user_id)',
        )->execute();

        $connection->createCommand(
            'CREATE TABLE IF NOT EXISTS knowledge_bases (
                id UUID PRIMARY KEY,
                user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                title VARCHAR(255) NOT NULL DEFAULT \'\',
                summary TEXT NOT NULL DEFAULT \'\',
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                deleted BOOLEAN NOT NULL DEFAULT FALSE,
                created_at TIMESTAMPTZ NOT NULL,
                updated_at TIMESTAMPTZ NOT NULL
            )',
        )->execute();

        $connection->createCommand(
            'CREATE INDEX IF NOT EXISTS idx_knowledge_bases_user_id ON knowledge_bases (user_id)',
        )->execute();
    },
];
