<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\KnowledgeBase\KnowledgeArticle;
use DateTimeImmutable;

final class KnowledgeArticleFactory
{
    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): KnowledgeArticle
    {
        return new KnowledgeArticle(
            id: (string) $row['id'],
            knowledgeBaseId: (string) $row['knowledge_base_id'],
            title: (string) $row['title'],
            content: (string) $row['content'],
            sortOrder: (int) $row['sort_order'],
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
