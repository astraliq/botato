<?php

declare(strict_types=1);

namespace App\Domain\Ai;

use DateTimeImmutable;

final class AiModel
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(
        private readonly string $id,
        private readonly string $providerId,
        private readonly string $code,
        private readonly string $name,
        private readonly AiModelPurpose $purpose,
        private readonly bool $isActive,
        private readonly bool $isDefault,
        private readonly int $sortOrder,
        private readonly array $settings,
        private readonly bool $deleted,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getProviderId(): string
    {
        return $this->providerId;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPurpose(): AiModelPurpose
    {
        return $this->purpose;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    /** @return array<string, mixed> */
    public function getSettings(): array
    {
        return $this->settings;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
