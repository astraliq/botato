<?php

declare(strict_types=1);

namespace App\Domain\Conversation;

use DateTimeImmutable;

final class ConversationMessage
{
    public function __construct(
        private readonly string $id,
        private readonly string $channelId,
        private readonly string $peerKey,
        private readonly ConversationMessageRole $role,
        private readonly string $content,
        private readonly ?string $externalId,
        private readonly DateTimeImmutable $createdAt,
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
