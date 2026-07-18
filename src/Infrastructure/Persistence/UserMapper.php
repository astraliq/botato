<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\User\User;
use App\Domain\User\UserGender;
use DateTimeImmutable;

final class UserMapper
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
        );
    }
}
