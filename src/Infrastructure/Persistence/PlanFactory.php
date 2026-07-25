<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Plan\Plan;

final class PlanFactory
{
    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): Plan
    {
        return new Plan(
            id: (string) $row['id'],
            code: (string) $row['code'],
            name: (string) $row['name'],
            description: (string) ($row['description'] ?? ''),
            sortOrder: (int) $row['sort_order'],
            isPopular: self::toBool($row['is_popular'] ?? false),
            priceMonthlyMinor: (int) $row['price_monthly_minor'],
            maxDialogsPerMonth: (int) $row['max_dialogs_per_month'],
            maxMessagesPerDialog: (int) ($row['max_messages_per_dialog'] ?? 10),
            maxChannels: isset($row['max_channels']) && $row['max_channels'] !== null
                ? (int) $row['max_channels']
                : null,
            maxKnowledgeBases: (int) $row['max_knowledge_bases'],
            buttonLabel: (string) ($row['button_label'] ?? 'Подключить'),
            isActive: self::toBool($row['is_active'] ?? true),
            deleted: self::toBool($row['deleted'] ?? false),
        );
    }

    private static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 't', 'true', 'yes'], true);
        }

        return false;
    }
}
