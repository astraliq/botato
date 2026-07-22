<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

return [
    'up' => static function (ConnectionInterface $connection): void {
        $connection->createCommand(
            'CREATE TABLE IF NOT EXISTS channels (
                id UUID PRIMARY KEY,
                user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                provider VARCHAR(32) NOT NULL,
                status VARCHAR(32) NOT NULL,
                external_id VARCHAR(255) NULL,
                display_name VARCHAR(255) NULL,
                settings JSONB NOT NULL DEFAULT \'{}\',
                connected_at TIMESTAMPTZ NULL,
                created_at TIMESTAMPTZ NOT NULL,
                updated_at TIMESTAMPTZ NOT NULL
            )',
        )->execute();

        $connection->createCommand(
            'CREATE INDEX IF NOT EXISTS idx_channels_user_id ON channels (user_id)',
        )->execute();

        $connection->createCommand(
            'CREATE INDEX IF NOT EXISTS idx_channels_user_provider ON channels (user_id, provider)',
        )->execute();

        $connection->createCommand(
            'CREATE INDEX IF NOT EXISTS idx_channels_provider_external_id ON channels (provider, external_id)',
        )->execute();

        $connection->createCommand(
            'CREATE UNIQUE INDEX IF NOT EXISTS uq_channels_user_telegram_connected
             ON channels (user_id, provider)
             WHERE provider = \'telegram\' AND status = \'connected\'',
        )->execute();

        $connection->createCommand(
            'CREATE TABLE IF NOT EXISTS channel_secrets (
                channel_id UUID PRIMARY KEY REFERENCES channels(id) ON DELETE CASCADE,
                payload_encrypted TEXT NOT NULL,
                secret_hint VARCHAR(16) NULL,
                updated_at TIMESTAMPTZ NOT NULL
            )',
        )->execute();
    },
];
