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
        private string $name,
        private AiModelPurpose $purpose,
        private bool $isActive,
        private bool $isDefault,
        private int $sortOrder,
        private array $settings,
        private bool $deleted,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    /**
     * @param array<string, mixed> $settings
     */
    public static function create(
        string $id,
        string $providerId,
        string $code,
        string $name,
        AiModelPurpose $purpose,
        bool $isActive,
        bool $isDefault,
        int $sortOrder,
        array $settings,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            providerId: $providerId,
            code: $code,
            name: $name,
            purpose: $purpose,
            isActive: $isActive,
            isDefault: $isDefault,
            sortOrder: $sortOrder,
            settings: $settings,
            deleted: false,
            createdAt: $createdAt,
            updatedAt: $createdAt,
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function update(
        string $name,
        AiModelPurpose $purpose,
        bool $isActive,
        bool $isDefault,
        int $sortOrder,
        array $settings,
        DateTimeImmutable $updatedAt,
    ): void {
        $this->name = $name;
        $this->purpose = $purpose;
        $this->isActive = $isActive;
        $this->isDefault = $isDefault;
        $this->sortOrder = $sortOrder;
        $this->settings = $settings;
        $this->updatedAt = $updatedAt;
    }

    public function markDeleted(DateTimeImmutable $updatedAt): void
    {
        $this->deleted = true;
        $this->isDefault = false;
        $this->isActive = false;
        $this->updatedAt = $updatedAt;
    }

    public function clearDefault(DateTimeImmutable $updatedAt): void
    {
        $this->isDefault = false;
        $this->updatedAt = $updatedAt;
    }

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
