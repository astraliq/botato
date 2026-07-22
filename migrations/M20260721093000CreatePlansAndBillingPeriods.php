<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

return [
    'up' => static function (ConnectionInterface $connection): void {
        $connection->createCommand(
            'CREATE TABLE IF NOT EXISTS plans (
                id UUID PRIMARY KEY,
                code VARCHAR(32) NOT NULL UNIQUE,
                name VARCHAR(64) NOT NULL,
                description TEXT NOT NULL DEFAULT \'\',
                sort_order INT NOT NULL DEFAULT 0,
                is_popular BOOLEAN NOT NULL DEFAULT FALSE,
                price_monthly_minor INT NOT NULL,
                max_dialogs_per_month INT NOT NULL,
                max_channels INT NULL,
                max_knowledge_bases INT NOT NULL,
                button_label VARCHAR(64) NOT NULL DEFAULT \'Подключить\',
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                deleted BOOLEAN NOT NULL DEFAULT FALSE,
                created_at TIMESTAMPTZ NOT NULL,
                updated_at TIMESTAMPTZ NOT NULL
            )',
        )->execute();

        $connection->createCommand(
            'CREATE TABLE IF NOT EXISTS billing_periods (
                id UUID PRIMARY KEY,
                code VARCHAR(16) NOT NULL UNIQUE,
                months INT NOT NULL,
                discount_percent NUMERIC(5, 2) NOT NULL,
                label VARCHAR(64) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMPTZ NOT NULL,
                updated_at TIMESTAMPTZ NOT NULL
            )',
        )->execute();

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:sP');

        $plans = [
            [
                'id' => 'a1000000-0000-4000-8000-000000000001',
                'code' => 'free',
                'name' => 'Free',
                'description' => 'Для знакомства с платформой',
                'sort_order' => 1,
                'is_popular' => false,
                'price_monthly_minor' => 0,
                'max_dialogs_per_month' => 10,
                'max_channels' => 1,
                'max_knowledge_bases' => 1,
                'button_label' => 'Начать бесплатно',
            ],
            [
                'id' => 'a1000000-0000-4000-8000-000000000002',
                'code' => 'start',
                'name' => 'Старт',
                'description' => 'Для фрилансеров и малого бизнеса',
                'sort_order' => 2,
                'is_popular' => false,
                'price_monthly_minor' => 49000,
                'max_dialogs_per_month' => 100,
                'max_channels' => 2,
                'max_knowledge_bases' => 3,
                'button_label' => 'Подключить',
            ],
            [
                'id' => 'a1000000-0000-4000-8000-000000000003',
                'code' => 'business',
                'name' => 'Бизнес',
                'description' => 'Для растущих команд',
                'sort_order' => 3,
                'is_popular' => true,
                'price_monthly_minor' => 149000,
                'max_dialogs_per_month' => 300,
                'max_channels' => 3,
                'max_knowledge_bases' => 10,
                'button_label' => 'Подключить',
            ],
            [
                'id' => 'a1000000-0000-4000-8000-000000000004',
                'code' => 'pro',
                'name' => 'Про',
                'description' => 'Для активного потока клиентов',
                'sort_order' => 4,
                'is_popular' => false,
                'price_monthly_minor' => 299000,
                'max_dialogs_per_month' => 1000,
                'max_channels' => null,
                'max_knowledge_bases' => 30,
                'button_label' => 'Подключить',
            ],
        ];

        foreach ($plans as $plan) {
            $connection
                ->createCommand(
                    'INSERT INTO plans (
                        id, code, name, description, sort_order, is_popular,
                        price_monthly_minor, max_dialogs_per_month, max_channels, max_knowledge_bases,
                        button_label, is_active, created_at, updated_at
                     ) VALUES (
                        :id, :code, :name, :description, :sort_order, :is_popular,
                        :price_monthly_minor, :max_dialogs_per_month, :max_channels, :max_knowledge_bases,
                        :button_label, TRUE, :created_at, :updated_at
                     )
                     ON CONFLICT (code) DO NOTHING',
                )
                ->bindValues([
                    ':id' => $plan['id'],
                    ':code' => $plan['code'],
                    ':name' => $plan['name'],
                    ':description' => $plan['description'],
                    ':sort_order' => $plan['sort_order'],
                    ':is_popular' => $plan['is_popular'],
                    ':price_monthly_minor' => $plan['price_monthly_minor'],
                    ':max_dialogs_per_month' => $plan['max_dialogs_per_month'],
                    ':max_channels' => $plan['max_channels'],
                    ':max_knowledge_bases' => $plan['max_knowledge_bases'],
                    ':button_label' => $plan['button_label'],
                    ':created_at' => $now,
                    ':updated_at' => $now,
                ])
                ->execute();
        }

        $periods = [
            [
                'id' => 'b1000000-0000-4000-8000-000000000001',
                'code' => 'm1',
                'months' => 1,
                'discount_percent' => '0.00',
                'label' => '1 мес',
                'sort_order' => 1,
            ],
            [
                'id' => 'b1000000-0000-4000-8000-000000000002',
                'code' => 'm6',
                'months' => 6,
                'discount_percent' => '15.00',
                'label' => '6 мес -15%',
                'sort_order' => 2,
            ],
            [
                'id' => 'b1000000-0000-4000-8000-000000000003',
                'code' => 'm12',
                'months' => 12,
                'discount_percent' => '25.00',
                'label' => '12 мес -25%',
                'sort_order' => 3,
            ],
        ];

        foreach ($periods as $period) {
            $connection
                ->createCommand(
                    'INSERT INTO billing_periods (
                        id, code, months, discount_percent, label, sort_order,
                        is_active, created_at, updated_at
                     ) VALUES (
                        :id, :code, :months, :discount_percent, :label, :sort_order,
                        TRUE, :created_at, :updated_at
                     )
                     ON CONFLICT (code) DO NOTHING',
                )
                ->bindValues([
                    ':id' => $period['id'],
                    ':code' => $period['code'],
                    ':months' => $period['months'],
                    ':discount_percent' => $period['discount_percent'],
                    ':label' => $period['label'],
                    ':sort_order' => $period['sort_order'],
                    ':created_at' => $now,
                    ':updated_at' => $now,
                ])
                ->execute();
        }
    },
];
