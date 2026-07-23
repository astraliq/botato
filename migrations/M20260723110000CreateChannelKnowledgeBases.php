<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

return [
    'up' => static function (ConnectionInterface $connection): void {
        $connection->createCommand(
            'CREATE TABLE IF NOT EXISTS channel_knowledge_bases (
                channel_id UUID NOT NULL REFERENCES channels(id) ON DELETE CASCADE,
                knowledge_base_id UUID NOT NULL REFERENCES knowledge_bases(id) ON DELETE CASCADE,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMPTZ NOT NULL,
                PRIMARY KEY (channel_id, knowledge_base_id)
            )',
        )->execute();

        $connection->createCommand(
            'CREATE INDEX IF NOT EXISTS idx_channel_knowledge_bases_kb
             ON channel_knowledge_bases (knowledge_base_id)',
        )->execute();

        $connection->createCommand(
            'INSERT INTO channel_knowledge_bases (channel_id, knowledge_base_id, sort_order, created_at)
             SELECT id, knowledge_base_id, 0, NOW()
             FROM channels
             WHERE knowledge_base_id IS NOT NULL
             ON CONFLICT DO NOTHING',
        )->execute();

        $connection->createCommand(
            'ALTER TABLE channels DROP COLUMN IF EXISTS knowledge_base_id',
        )->execute();
    },
];
