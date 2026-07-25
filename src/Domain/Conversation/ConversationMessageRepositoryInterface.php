<?php

declare(strict_types=1);

namespace App\Domain\Conversation;

use DateTimeImmutable;

interface ConversationMessageRepositoryInterface
{
    /**
     * Сохраняет сообщение. При совпадении external_id возвращает уже существующую запись.
     */
    public function append(
        string $channelId,
        string $peerKey,
        ConversationMessageRole $role,
        string $content,
        ?string $externalId = null,
    ): ConversationMessage;

    public function findByExternalId(
        string $channelId,
        string $peerKey,
        string $externalId,
    ): ?ConversationMessage;

    /**
     * Сообщения диалога с указанного момента в хронологическом порядке (от старых к новым).
     *
     * @return list<ConversationMessage>
     */
    public function listSince(
        string $channelId,
        string $peerKey,
        DateTimeImmutable $since,
        ?DateTimeImmutable $until = null,
        ?int $limit = null,
    ): array;
}
