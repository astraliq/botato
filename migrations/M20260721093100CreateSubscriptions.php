<?php

declare(strict_types=1);

use Ramsey\Uuid\Uuid;
use Yiisoft\Db\Connection\ConnectionInterface;

return [
    'up' => static function (ConnectionInterface $connection): void {
        $connection->createCommand(
            'CREATE TABLE IF NOT EXISTS subscriptions (
                id UUID PRIMARY KEY,
                user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                plan_id UUID NOT NULL REFERENCES plans(id),
                billing_period_id UUID NOT NULL REFERENCES billing_periods(id),
                status VARCHAR(32) NOT NULL,
                price_monthly_minor INT NOT NULL,
                amount_charged_minor INT NOT NULL,
                currency CHAR(3) NOT NULL DEFAULT \'RUB\',
                max_dialogs_per_month INT NOT NULL,
                max_channels INT NULL,
                max_knowledge_bases INT NOT NULL,
                starts_at TIMESTAMPTZ NOT NULL,
                ends_at TIMESTAMPTZ NOT NULL,
                created_at TIMESTAMPTZ NOT NULL,
                updated_at TIMESTAMPTZ NOT NULL
            )',
        )->execute();

        $connection->createCommand(
            'CREATE INDEX IF NOT EXISTS idx_subscriptions_user_status
             ON subscriptions (user_id, status)',
        )->execute();

        $connection->createCommand(
            'CREATE UNIQUE INDEX IF NOT EXISTS uq_subscriptions_user_active
             ON subscriptions (user_id)
             WHERE status = \'active\'',
        )->execute();

        $now = new \DateTimeImmutable();
        $nowFormatted = $now->format('Y-m-d H:i:sP');
        $endsAt = $now->modify('+100 years')->format('Y-m-d H:i:sP');

        $plan = $connection
            ->createCommand('SELECT * FROM plans WHERE code = :code')
            ->bindValue(':code', 'free')
            ->queryOne();

        $period = $connection
            ->createCommand('SELECT * FROM billing_periods WHERE code = :code')
            ->bindValue(':code', 'm1')
            ->queryOne();

        if (!is_array($plan) || !is_array($period)) {
            throw new \RuntimeException('Не найдены seed-записи тарифа free или периода m1.');
        }

        $userIds = $connection
            ->createCommand('SELECT id FROM users')
            ->queryColumn();

        foreach ($userIds as $userId) {
            $connection
                ->createCommand(
                    'INSERT INTO subscriptions (
                        id, user_id, plan_id, billing_period_id, status,
                        price_monthly_minor, amount_charged_minor, currency,
                        max_dialogs_per_month, max_channels, max_knowledge_bases,
                        starts_at, ends_at, created_at, updated_at
                     ) VALUES (
                        :id, :user_id, :plan_id, :billing_period_id, :status,
                        :price_monthly_minor, :amount_charged_minor, :currency,
                        :max_dialogs_per_month, :max_channels, :max_knowledge_bases,
                        :starts_at, :ends_at, :created_at, :updated_at
                     )',
                )
                ->bindValues([
                    ':id' => Uuid::uuid4()->toString(),
                    ':user_id' => (string) $userId,
                    ':plan_id' => (string) $plan['id'],
                    ':billing_period_id' => (string) $period['id'],
                    ':status' => 'active',
                    ':price_monthly_minor' => (int) $plan['price_monthly_minor'],
                    ':amount_charged_minor' => 0,
                    ':currency' => 'RUB',
                    ':max_dialogs_per_month' => (int) $plan['max_dialogs_per_month'],
                    ':max_channels' => $plan['max_channels'] !== null ? (int) $plan['max_channels'] : null,
                    ':max_knowledge_bases' => (int) $plan['max_knowledge_bases'],
                    ':starts_at' => $nowFormatted,
                    ':ends_at' => $endsAt,
                    ':created_at' => $nowFormatted,
                    ':updated_at' => $nowFormatted,
                ])
                ->execute();
        }
    },
];
