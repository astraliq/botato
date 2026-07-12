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

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
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
