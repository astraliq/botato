<?php

declare(strict_types=1);

namespace App\Domain\Conversation;

use DateTimeImmutable;

final readonly class ConversationMessage
{
    public function __construct(
        private string $id,
        private string $channelId,
        private string $peerKey,
        private ConversationMessageRole $role,
        private string $content,
        private ?string $externalId,
        private DateTimeImmutable $createdAt,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getChannelId(): string
    {
        return $this->channelId;
    }

    public function getPeerKey(): string
    {
        return $this->peerKey;
    }

    public function getRole(): ConversationMessageRole
    {
        return $this->role;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
