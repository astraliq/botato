<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

return [
    'up' => static function (ConnectionInterface $connection): void {
        $connection->createCommand(
            'ALTER TABLE knowledge_bases
             ADD COLUMN IF NOT EXISTS summary TEXT NOT NULL DEFAULT \'\'',
        )->execute();

        $connection->createCommand(
            'ALTER TABLE knowledge_bases
             ADD COLUMN IF NOT EXISTS is_active BOOLEAN NOT NULL DEFAULT TRUE',
        )->execute();

        $connection->createCommand(
            'ALTER TABLE knowledge_bases
             ADD COLUMN IF NOT EXISTS deleted BOOLEAN NOT NULL DEFAULT FALSE',
        )->execute();

        $connection->createCommand(
            'CREATE TABLE IF NOT EXISTS knowledge_articles (
                id UUID PRIMARY KEY,
                knowledge_base_id UUID NOT NULL REFERENCES knowledge_bases(id) ON DELETE CASCADE,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                deleted BOOLEAN NOT NULL DEFAULT FALSE,
                created_at TIMESTAMPTZ NOT NULL,
                updated_at TIMESTAMPTZ NOT NULL
            )',
        )->execute();

        $connection->createCommand(
            'CREATE INDEX IF NOT EXISTS idx_knowledge_articles_kb_sort
             ON knowledge_articles (knowledge_base_id, sort_order)',
        )->execute();

        $connection->createCommand(
            'ALTER TABLE channels
             ADD COLUMN IF NOT EXISTS knowledge_base_id UUID NULL
             REFERENCES knowledge_bases(id) ON DELETE SET NULL',
        )->execute();

        $connection->createCommand(
            'CREATE INDEX IF NOT EXISTS idx_channels_knowledge_base_id
             ON channels (knowledge_base_id)',
        )->execute();
    },
];
