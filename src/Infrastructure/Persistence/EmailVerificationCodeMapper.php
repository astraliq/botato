<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Auth\EmailVerificationCode;
use DateTimeImmutable;

final class EmailVerificationCodeMapper
{
    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): EmailVerificationCode
    {
        return new EmailVerificationCode(
            id: (int) $row['id'],
            email: (string) $row['email'],
            codeHash: (string) $row['code_hash'],
            expiresAt: new DateTimeImmutable((string) $row['expires_at']),
            createdAt: new DateTimeImmutable((string) $row['created_at']),
            usedAt: isset($row['used_at']) && $row['used_at'] !== null
                ? new DateTimeImmutable((string) $row['used_at'])
                : null,
        );
    }
}
