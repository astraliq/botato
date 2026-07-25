<?php

declare(strict_types=1);

namespace App\Domain\Ai;

use DateTimeImmutable;

final class AiProvider
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(
        private readonly string $id,
        private readonly string $code,
        private string $name,
        private string $baseUrl,
        private string $apiHost,
        private AiApiStyle $apiStyle,
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
        string $code,
        string $name,
        string $baseUrl,
        string $apiHost,
        AiApiStyle $apiStyle,
        bool $isActive,
        bool $isDefault,
        int $sortOrder,
        array $settings,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            code: $code,
            name: $name,
            baseUrl: $baseUrl,
            apiHost: $apiHost,
            apiStyle: $apiStyle,
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
        string $baseUrl,
        string $apiHost,
        AiApiStyle $apiStyle,
        bool $isActive,
        bool $isDefault,
        int $sortOrder,
        array $settings,
        DateTimeImmutable $updatedAt,
    ): void {
        $this->name = $name;
        $this->baseUrl = $baseUrl;
        $this->apiHost = $apiHost;
        $this->apiStyle = $apiStyle;
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

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getApiHost(): string
    {
        return $this->apiHost;
    }

    public function getApiStyle(): AiApiStyle
    {
        return $this->apiStyle;
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
