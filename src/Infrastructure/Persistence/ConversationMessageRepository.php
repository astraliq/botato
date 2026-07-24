<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Conversation\ConversationMessage;
use App\Domain\Conversation\ConversationMessageRepositoryInterface;
use App\Domain\Conversation\ConversationMessageRole;
use App\Shared\ArrayHelper;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Yiisoft\Db\Connection\ConnectionInterface;

final class ConversationMessageRepository implements ConversationMessageRepositoryInterface
{
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {}

    public function append(
        string $channelId,
        string $peerKey,
        ConversationMessageRole $role,
        string $content,
        ?string $externalId = null,
    ): ConversationMessage {
        $id = Uuid::uuid4()->toString();
        $createdAt = new DateTimeImmutable();
        $createdAtFormatted = $createdAt->format('Y-m-d H:i:sP');

        if ($externalId !== null && $externalId !== '') {
            $this->connection
                ->createCommand(
                    'INSERT INTO conversation_messages (
                        id, channel_id, peer_key, role, content, external_id, created_at
                     ) VALUES (
                        :id, :channel_id, :peer_key, :role, :content, :external_id, :created_at
                     )
                     ON CONFLICT (channel_id, peer_key, external_id) WHERE external_id IS NOT NULL
                     DO NOTHING',
                )
                ->bindValues([
                    ':id' => $id,
                    ':channel_id' => $channelId,
                    ':peer_key' => $peerKey,
                    ':role' => $role->value,
                    ':content' => $content,
                    ':external_id' => $externalId,
                    ':created_at' => $createdAtFormatted,
                ])
                ->execute();

            $row = $this->connection
                ->createCommand(
                    'SELECT * FROM conversation_messages
                     WHERE channel_id = :channel_id
                       AND peer_key = :peer_key
                       AND external_id = :external_id
                     LIMIT 1',
                )
                ->bindValues([
                    ':channel_id' => $channelId,
                    ':peer_key' => $peerKey,
                    ':external_id' => $externalId,
                ])
                ->queryOne();

            if (!is_array($row)) {
                throw new RuntimeException('Не удалось сохранить сообщение диалога.');
            }

            return ConversationMessageFactory::fromRow(ArrayHelper::toStringKeyArray($row));
        }

        $this->connection
            ->createCommand(
                'INSERT INTO conversation_messages (
                    id, channel_id, peer_key, role, content, external_id, created_at
                 ) VALUES (
                    :id, :channel_id, :peer_key, :role, :content, NULL, :created_at
                 )',
            )
            ->bindValues([
                ':id' => $id,
                ':channel_id' => $channelId,
                ':peer_key' => $peerKey,
                ':role' => $role->value,
                ':content' => $content,
                ':created_at' => $createdAtFormatted,
            ])
            ->execute();

        return new ConversationMessage(
            id: $id,
            channelId: $channelId,
            peerKey: $peerKey,
            role: $role,
            content: $content,
            externalId: null,
            createdAt: $createdAt,
        );
    }

    public function listRecent(string $channelId, string $peerKey, int $limit): array
    {
        $limit = max(1, $limit);

        $rows = $this->connection
            ->createCommand(
                'SELECT * FROM (
                    SELECT * FROM conversation_messages
                    WHERE channel_id = :channel_id AND peer_key = :peer_key
                    ORDER BY created_at DESC, id DESC
                    LIMIT ' . $limit . '
                 ) recent
                 ORDER BY created_at ASC, id ASC',
            )
            ->bindValues([
                ':channel_id' => $channelId,
                ':peer_key' => $peerKey,
            ])
            ->queryAll();

        $messages = [];

        foreach ($rows as $row) {
            $messages[] = ConversationMessageFactory::fromRow(ArrayHelper::toStringKeyArray($row));
        }

        return $messages;
    }
}
