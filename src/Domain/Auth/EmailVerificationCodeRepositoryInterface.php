<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use DateTimeImmutable;

interface EmailVerificationCodeRepositoryInterface
{
    public function save(string $email, string $codeHash, DateTimeImmutable $expiresAt): void;

    public function findLatestValid(string $email, DateTimeImmutable $now): ?EmailVerificationCode;

    public function markUsed(int $id, DateTimeImmutable $usedAt): void;

    public function hasRecentRequest(string $email, DateTimeImmutable $since): bool;
}
