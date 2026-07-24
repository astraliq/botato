<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Conversation\ConversationMessage;
use App\Domain\Conversation\ConversationMessageRole;
use DateTimeImmutable;

final class ConversationMessageFactory
{
    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): ConversationMessage
    {
        return new ConversationMessage(
            id: (string) $row['id'],
            channelId: (string) $row['channel_id'],
            peerKey: (string) $row['peer_key'],
            role: ConversationMessageRole::from((string) $row['role']),
            content: (string) $row['content'],
            externalId: isset($row['external_id']) && $row['external_id'] !== null && $row['external_id'] !== ''
                ? (string) $row['external_id']
                : null,
            createdAt: new DateTimeImmutable((string) $row['created_at']),
        );
    }
}
