<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Support\SupportTicket;
use DateTimeImmutable;

final class SupportTicketFactory
{
    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): SupportTicket
    {
        return new SupportTicket(
            id: (string) $row['id'],
            userId: (string) $row['user_id'],
            question: (string) $row['question'],
            isClosed: self::toBool($row['is_closed'] ?? false),
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