<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Ai\AiApiStyle;
use App\Domain\Ai\AiProvider;
use DateTimeImmutable;

final class AiProviderFactory
{
    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): AiProvider
    {
        return new AiProvider(
            id: (string) $row['id'],
            code: (string) $row['code'],
            name: (string) $row['name'],
            baseUrl: (string) $row['base_url'],
            apiHost: (string) $row['api_host'],
            apiStyle: AiApiStyle::from((string) $row['api_style']),
            isActive: self::toBool($row['is_active'] ?? true),
            isDefault: self::toBool($row['is_default'] ?? false),
            sortOrder: (int) $row['sort_order'],
            settings: self::decodeSettings($row['settings'] ?? []),
            deleted: self::toBool($row['deleted'] ?? false),
            createdAt: new DateTimeImmutable((string) $row['created_at']),
            updatedAt: new DateTimeImmutable((string) $row['updated_at']),
        );
    }

    /** @return array<string, mixed> */
    private static function decodeSettings(mixed $settings): array
    {
        if (is_array($settings)) {
            $result = [];

            foreach ($settings as $key => $value) {
                if (is_string($key)) {
                    $result[$key] = $value;
                }
            }

            return $result;
        }

        if (is_string($settings) && $settings !== '') {
            $decoded = json_decode($settings, true);

            return is_array($decoded) ? self::decodeSettings($decoded) : [];
        }

        return [];
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
