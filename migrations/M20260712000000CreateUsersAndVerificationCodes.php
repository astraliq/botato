<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

return [
    'up' => static function (ConnectionInterface $connection): void {
        $connection->createCommand(
            'CREATE TABLE IF NOT EXISTS users (
                id UUID PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                surname VARCHAR(255) NOT NULL DEFAULT \'\',
                name VARCHAR(255) NOT NULL DEFAULT \'\',
                patronymic VARCHAR(255) NOT NULL DEFAULT \'\',
                company_name VARCHAR(255) NOT NULL DEFAULT \'\',
                description TEXT NOT NULL DEFAULT \'\',
                phone VARCHAR(20) NOT NULL DEFAULT \'\',
                email_verified_at TIMESTAMPTZ NULL,
                created_at TIMESTAMPTZ NOT NULL,
                updated_at TIMESTAMPTZ NOT NULL
            )',
        )->execute();

        $connection->createCommand(
            'CREATE TABLE IF NOT EXISTS email_verification_codes (
                id BIGSERIAL PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                code_hash VARCHAR(255) NOT NULL,
                expires_at TIMESTAMPTZ NOT NULL,
                used_at TIMESTAMPTZ NULL,
                created_at TIMESTAMPTZ NOT NULL
            )',
        )->execute();

        $connection->createCommand(
            'CREATE INDEX IF NOT EXISTS idx_email_verification_codes_email_expires
             ON email_verification_codes (email, expires_at)',
        )->execute();
    },
];
