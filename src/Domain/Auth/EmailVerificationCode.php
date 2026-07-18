<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use DateTimeImmutable;

final class EmailVerificationCode
{
    public function __construct(
        private readonly int $id,
        private readonly string $email,
        private readonly string $codeHash,
        private readonly DateTimeImmutable $expiresAt,
        private readonly DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $usedAt = null,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCodeHash(): string
    {
        return $this->codeHash;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUsedAt(): ?DateTimeImmutable
    {
        return $this->usedAt;
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $this->expiresAt <= $now;
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }
}
