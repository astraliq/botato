<?php

declare(strict_types=1);

namespace App\Domain\Conversation;

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

    /**
     * Последние сообщения диалога в хронологическом порядке (от старых к новым).
     *
     * @return list<ConversationMessage>
     */
    public function listRecent(string $channelId, string $peerKey, int $limit): array;
}
