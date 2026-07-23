<?php

declare(strict_types=1);

namespace App\Domain\Channel;

use DateTimeImmutable;
use InvalidArgumentException;

final class Channel
{
    /**
     * @param array<string, mixed> $settings
     * @param list<string> $knowledgeBaseIds
     */
    public function __construct(
        private readonly string $id,
        private readonly string $userId,
        private readonly ChannelProvider $provider,
        private ChannelStatus $status,
        private ?string $externalId,
        private ?string $displayName,
        private array $settings,
        private ?DateTimeImmutable $connectedAt,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private array $knowledgeBaseIds = [],
    ) {}

    /**
     * @param array<string, mixed> $settings
     * @param list<string> $knowledgeBaseIds
     */
    public static function create(
        string $id,
        string $userId,
        ChannelProvider $provider,
        DateTimeImmutable $createdAt,
        array $settings = [],
        array $knowledgeBaseIds = [],
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            provider: $provider,
            status: ChannelStatus::Disconnected,
            externalId: null,
            displayName: null,
            settings: $settings,
            connectedAt: null,
            createdAt: $createdAt,
            updatedAt: $createdAt,
            knowledgeBaseIds: $knowledgeBaseIds,
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getProvider(): ChannelProvider
    {
        return $this->provider;
    }

    public function getStatus(): ChannelStatus
    {
        return $this->status;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    /** @return array<string, mixed> */
    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getConnectedAt(): ?DateTimeImmutable
    {
        return $this->connectedAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return list<string> */
    public function getKnowledgeBaseIds(): array
    {
        return $this->knowledgeBaseIds;
    }

    public function hasKnowledgeBase(string $knowledgeBaseId): bool
    {
        return in_array($knowledgeBaseId, $this->knowledgeBaseIds, true);
    }

    /** @param list<string> $knowledgeBaseIds */
    public function assignKnowledgeBases(array $knowledgeBaseIds, DateTimeImmutable $updatedAt): void
    {
        $normalized = [];

        foreach ($knowledgeBaseIds as $knowledgeBaseId) {
            if (!is_string($knowledgeBaseId) || $knowledgeBaseId === '') {
                continue;
            }

            if (!in_array($knowledgeBaseId, $normalized, true)) {
                $normalized[] = $knowledgeBaseId;
            }
        }

        $this->knowledgeBaseIds = $normalized;
        $this->updatedAt = $updatedAt;
    }

    public function connectTelegram(string $externalId, string $displayName, DateTimeImmutable $connectedAt): void
    {
        if ($this->provider !== ChannelProvider::Telegram) {
            throw new InvalidArgumentException('Канал не является Telegram.');
        }

        $this->externalId = $externalId;
        $this->displayName = $displayName;
        $this->status = ChannelStatus::Connected;
        $this->connectedAt = $connectedAt;
        $this->updatedAt = $connectedAt;
    }

    public function setWebhookSecret(string $webhookSecret, DateTimeImmutable $updatedAt): void
    {
        $this->settings['webhook_secret'] = $webhookSecret;
        $this->updatedAt = $updatedAt;
    }

    public function getWebhookSecret(): ?string
    {
        $webhookSecret = $this->settings['webhook_secret'] ?? null;

        return is_string($webhookSecret) && $webhookSecret !== '' ? $webhookSecret : null;
    }

    public function rememberBusinessConnection(
        string $businessConnectionId,
        int $ownerUserId,
        DateTimeImmutable $updatedAt,
    ): void {
        $connections = $this->settings['business_connections'] ?? [];

        if (!is_array($connections)) {
            $connections = [];
        }

        $connections[$businessConnectionId] = [
            'owner_user_id' => $ownerUserId,
        ];
        $this->settings['business_connections'] = $connections;
        $this->updatedAt = $updatedAt;
    }

    public function getBusinessOwnerUserId(string $businessConnectionId): ?int
    {
        $connections = $this->settings['business_connections'] ?? null;

        if (!is_array($connections)) {
            return null;
        }

        $connection = $connections[$businessConnectionId] ?? null;

        if (!is_array($connection)) {
            return null;
        }

        $ownerUserId = $connection['owner_user_id'] ?? null;

        return is_int($ownerUserId) ? $ownerUserId : (is_numeric($ownerUserId) ? (int) $ownerUserId : null);
    }

    public function disconnect(DateTimeImmutable $updatedAt): void
    {
        $this->status = ChannelStatus::Disconnected;
        $this->connectedAt = null;
        $this->updatedAt = $updatedAt;
    }

    public function markError(DateTimeImmutable $updatedAt): void
    {
        $this->status = ChannelStatus::Error;
        $this->updatedAt = $updatedAt;
    }
}
