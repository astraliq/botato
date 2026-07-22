<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\KnowledgeBase\KnowledgeBase;
use DateTimeImmutable;

final class KnowledgeBaseFactory
{
    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): KnowledgeBase
    {
        return new KnowledgeBase(
            id: (string) $row['id'],
            userId: (string) $row['user_id'],
            title: (string) $row['title'],
            summary: (string) ($row['summary'] ?? ''),
            isActive: self::toBool($row['is_active'] ?? true),
            deleted: self::toBool($row['deleted'] ?? false),
            createdAt: new DateTimeImmutable((string) $row['created_at']),
            updatedAt: new DateTimeImmutable((string) $row['updated_at']),
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
