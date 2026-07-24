<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

return [
    'up' => static function (ConnectionInterface $connection): void {
        $connection->createCommand(
            'CREATE TABLE IF NOT EXISTS conversation_messages (
                id UUID PRIMARY KEY,
                channel_id UUID NOT NULL REFERENCES channels(id) ON DELETE CASCADE,
                peer_key TEXT NOT NULL,
                role VARCHAR(16) NOT NULL,
                content TEXT NOT NULL,
                external_id TEXT NULL,
                created_at TIMESTAMPTZ NOT NULL
            )',
        )->execute();

        $connection->createCommand(
            'CREATE INDEX IF NOT EXISTS idx_conversation_messages_channel_peer_created
             ON conversation_messages (channel_id, peer_key, created_at DESC)',
        )->execute();

        $connection->createCommand(
            'CREATE UNIQUE INDEX IF NOT EXISTS uq_conversation_messages_channel_peer_external
             ON conversation_messages (channel_id, peer_key, external_id)
             WHERE external_id IS NOT NULL',
        )->execute();
    },
];
