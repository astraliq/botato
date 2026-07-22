<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

return [
    'up' => static function (ConnectionInterface $connection): void {
        $connection->createCommand(
            'CREATE TABLE IF NOT EXISTS usage_peer_windows (
                id UUID PRIMARY KEY,
                user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                peer_key VARCHAR(64) NOT NULL,
                window_started_at TIMESTAMPTZ NOT NULL,
                messages_count INT NOT NULL DEFAULT 0,
                updated_at TIMESTAMPTZ NOT NULL,
                CONSTRAINT uq_usage_peer_windows_user_peer UNIQUE (user_id, peer_key)
            )',
        )->execute();

        $connection->createCommand(
            'CREATE INDEX IF NOT EXISTS idx_usage_peer_windows_user_id
             ON usage_peer_windows (user_id)',
        )->execute();
    },
];
