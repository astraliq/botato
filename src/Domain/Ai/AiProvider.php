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
        private readonly string $name,
        private readonly string $baseUrl,
        private readonly string $apiHost,
        private readonly AiApiStyle $apiStyle,
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
