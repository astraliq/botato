<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\User\User;
use App\Domain\User\UserGender;
use DateTimeImmutable;

final class UserFactory
{
    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): User
    {
        return new User(
            id: (string) $row['id'],
            email: (string) $row['email'],
            surname: (string) ($row['surname'] ?? ''),
            name: (string) ($row['name'] ?? ''),
            patronymic: (string) ($row['patronymic'] ?? ''),
            companyName: (string) ($row['company_name'] ?? ''),
            description: (string) ($row['description'] ?? ''),
            phone: (string) ($row['phone'] ?? ''),
            gender: isset($row['gender']) && $row['gender'] !== null && $row['gender'] !== ''
                ? UserGender::tryFrom((string) $row['gender'])
                : null,
            emailVerifiedAt: isset($row['email_verified_at']) && $row['email_verified_at'] !== null
                ? new DateTimeImmutable((string) $row['email_verified_at'])
                : null,
            createdAt: new DateTimeImmutable((string) $row['created_at']),
            updatedAt: new DateTimeImmutable((string) $row['updated_at']),
            deleted: self::toBool($row['deleted'] ?? false),
            isAdmin: self::toBool($row['is_admin'] ?? false),
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
