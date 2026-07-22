<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

return [
    'up' => static function (ConnectionInterface $connection): void {
        $connection->createCommand(
            'CREATE TABLE IF NOT EXISTS ai_providers (
                id UUID PRIMARY KEY,
                code VARCHAR(32) NOT NULL UNIQUE,
                name VARCHAR(128) NOT NULL,
                base_url TEXT NOT NULL,
                api_host VARCHAR(255) NOT NULL,
                api_style VARCHAR(32) NOT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                is_default BOOLEAN NOT NULL DEFAULT FALSE,
                sort_order INT NOT NULL DEFAULT 0,
                settings JSONB NOT NULL DEFAULT \'{}\',
                deleted BOOLEAN NOT NULL DEFAULT FALSE,
                created_at TIMESTAMPTZ NOT NULL,
                updated_at TIMESTAMPTZ NOT NULL
            )',
        )->execute();

        $connection->createCommand(
            'CREATE UNIQUE INDEX IF NOT EXISTS uq_ai_providers_default_active
             ON ai_providers (is_default)
             WHERE is_default = TRUE AND deleted = FALSE',
        )->execute();

        $connection->createCommand(
            'CREATE TABLE IF NOT EXISTS ai_provider_secrets (
                provider_id UUID PRIMARY KEY REFERENCES ai_providers(id) ON DELETE CASCADE,
                payload_encrypted TEXT NOT NULL,
                secret_hint VARCHAR(16) NULL,
                updated_at TIMESTAMPTZ NOT NULL
            )',
        )->execute();

        $connection->createCommand(
            'CREATE TABLE IF NOT EXISTS ai_models (
                id UUID PRIMARY KEY,
                provider_id UUID NOT NULL REFERENCES ai_providers(id) ON DELETE CASCADE,
                code VARCHAR(128) NOT NULL,
                name VARCHAR(128) NOT NULL,
                purpose VARCHAR(32) NOT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                is_default BOOLEAN NOT NULL DEFAULT FALSE,
                sort_order INT NOT NULL DEFAULT 0,
                settings JSONB NOT NULL DEFAULT \'{}\',
                deleted BOOLEAN NOT NULL DEFAULT FALSE,
                created_at TIMESTAMPTZ NOT NULL,
                updated_at TIMESTAMPTZ NOT NULL
            )',
        )->execute();

        $connection->createCommand(
            'CREATE UNIQUE INDEX IF NOT EXISTS uq_ai_models_provider_code
             ON ai_models (provider_id, code)
             WHERE deleted = FALSE',
        )->execute();

        $connection->createCommand(
            'CREATE UNIQUE INDEX IF NOT EXISTS uq_ai_models_provider_purpose_default
             ON ai_models (provider_id, purpose)
             WHERE is_default = TRUE AND deleted = FALSE AND is_active = TRUE',
        )->execute();

        $connection->createCommand(
            'CREATE INDEX IF NOT EXISTS idx_ai_models_provider_id ON ai_models (provider_id)',
        )->execute();

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:sP');
        $providerId = 'c1000000-0000-4000-8000-000000000001';
        $modelId = 'c2000000-0000-4000-8000-000000000001';
        $settingsJson = json_encode(
            [
                'chat_completions_path' => '/chat/completions',
                'default_max_tokens' => 4096,
            ],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
        );

        $connection
            ->createCommand(
                'INSERT INTO ai_providers (
                    id, code, name, base_url, api_host, api_style,
                    is_active, is_default, sort_order, settings,
                    deleted, created_at, updated_at
                 ) VALUES (
                    :id, :code, :name, :base_url, :api_host, :api_style,
                    TRUE, TRUE, 1, CAST(:settings AS JSONB),
                    FALSE, :created_at, :updated_at
                 )
                 ON CONFLICT (code) DO NOTHING',
            )
            ->bindValues([
                ':id' => $providerId,
                ':code' => 'aitunnel',
                ':name' => 'AITUNNEL',
                ':base_url' => 'https://api.aitunnel.ru/v1/',
                ':api_host' => 'api.aitunnel.ru',
                ':api_style' => 'openai_compatible',
                ':settings' => $settingsJson,
                ':created_at' => $now,
                ':updated_at' => $now,
            ])
            ->execute();

        $connection
            ->createCommand(
                'INSERT INTO ai_models (
                    id, provider_id, code, name, purpose,
                    is_active, is_default, sort_order, settings,
                    deleted, created_at, updated_at
                 ) VALUES (
                    :id, :provider_id, :code, :name, :purpose,
                    TRUE, TRUE, 1, CAST(\'{}\' AS JSONB),
                    FALSE, :created_at, :updated_at
                 )
                 ON CONFLICT (id) DO NOTHING',
            )
            ->bindValues([
                ':id' => $modelId,
                ':provider_id' => $providerId,
                ':code' => 'auto',
                ':name' => 'Автовыбор',
                ':purpose' => 'chat',
                ':created_at' => $now,
                ':updated_at' => $now,
            ])
            ->execute();
    },
];
