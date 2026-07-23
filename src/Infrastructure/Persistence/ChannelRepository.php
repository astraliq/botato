<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Channel\Channel;
use App\Domain\Channel\ChannelProvider;
use App\Domain\Channel\ChannelRepositoryInterface;
use App\Domain\Security\SecretEncryptorInterface;
use App\Shared\ArrayHelper;
use DateTimeImmutable;
use Yiisoft\Db\Connection\ConnectionInterface;

final class ChannelRepository implements ChannelRepositoryInterface
{
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly SecretEncryptorInterface $secretEncryptor,
    ) {}

    public function findById(string $id): ?Channel
    {
        $row = $this->connection
            ->createCommand('SELECT * FROM channels WHERE id = :id')
            ->bindValue(':id', $id)
            ->queryOne();

        return is_array($row) ? $this->mapRow(ArrayHelper::toStringKeyArray($row)) : null;
    }

    public function findConnectedByUserAndProvider(string $userId, ChannelProvider $provider): ?Channel
    {
        $row = $this->connection
            ->createCommand(
                'SELECT * FROM channels
                 WHERE user_id = :user_id
                   AND provider = :provider
                   AND status = :status
                 LIMIT 1',
            )
            ->bindValues([
                ':user_id' => $userId,
                ':provider' => $provider->value,
                ':status' => 'connected',
            ])
            ->queryOne();

        return is_array($row) ? $this->mapRow(ArrayHelper::toStringKeyArray($row)) : null;
    }

    public function listByUserId(string $userId): array
    {
        $rows = $this->connection
            ->createCommand(
                'SELECT * FROM channels WHERE user_id = :user_id ORDER BY created_at ASC',
            )
            ->bindValue(':user_id', $userId)
            ->queryAll();

        $channels = [];

        foreach ($rows as $row) {
            $channels[] = $this->mapRow(ArrayHelper::toStringKeyArray($row));
        }

        return $channels;
    }

    public function save(Channel $channel, ?string $secretPayloadJson = null): void
    {
        $existing = $this->connection
            ->createCommand('SELECT 1 FROM channels WHERE id = :id')
            ->bindValue(':id', $channel->getId())
            ->queryScalar();
        $settingsJson = json_encode($channel->getSettings(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        if ($existing === false || $existing === null) {
            $this->connection
                ->createCommand(
                    'INSERT INTO channels (
                        id, user_id, provider, status, external_id, display_name, settings,
                        connected_at, created_at, updated_at
                     ) VALUES (
                        :id, :user_id, :provider, :status, :external_id, :display_name, CAST(:settings AS JSONB),
                        :connected_at, :created_at, :updated_at
                     )',
                )
                ->bindValues([
                    ':id' => $channel->getId(),
                    ':user_id' => $channel->getUserId(),
                    ':provider' => $channel->getProvider()->value,
                    ':status' => $channel->getStatus()->value,
                    ':external_id' => $channel->getExternalId(),
                    ':display_name' => $channel->getDisplayName(),
                    ':settings' => $settingsJson,
                    ':connected_at' => $channel->getConnectedAt()?->format('Y-m-d H:i:sP'),
                    ':created_at' => $channel->getCreatedAt()->format('Y-m-d H:i:sP'),
                    ':updated_at' => $channel->getUpdatedAt()->format('Y-m-d H:i:sP'),
                ])
                ->execute();
        } else {
            $this->connection
                ->createCommand(
                    'UPDATE channels SET
                        status = :status,
                        external_id = :external_id,
                        display_name = :display_name,
                        settings = CAST(:settings AS JSONB),
                        connected_at = :connected_at,
                        updated_at = :updated_at
                     WHERE id = :id',
                )
                ->bindValues([
                    ':id' => $channel->getId(),
                    ':status' => $channel->getStatus()->value,
                    ':external_id' => $channel->getExternalId(),
                    ':display_name' => $channel->getDisplayName(),
                    ':settings' => $settingsJson,
                    ':connected_at' => $channel->getConnectedAt()?->format('Y-m-d H:i:sP'),
                    ':updated_at' => $channel->getUpdatedAt()->format('Y-m-d H:i:sP'),
                ])
                ->execute();
        }

        $this->syncKnowledgeBases($channel);

        if ($secretPayloadJson !== null) {
            $this->upsertSecret($channel->getId(), $secretPayloadJson);
        }
    }

    public function getSecretPayload(string $channelId): ?string
    {
        $row = $this->connection
            ->createCommand(
                'SELECT payload_encrypted FROM channel_secrets WHERE channel_id = :channel_id',
            )
            ->bindValue(':channel_id', $channelId)
            ->queryOne();

        if (!is_array($row) || !isset($row['payload_encrypted'])) {
            return null;
        }

        return $this->secretEncryptor->decrypt((string) $row['payload_encrypted']);
    }

    public function findSecretHint(string $channelId): ?string
    {
        $row = $this->connection
            ->createCommand(
                'SELECT secret_hint FROM channel_secrets WHERE channel_id = :channel_id',
            )
            ->bindValue(':channel_id', $channelId)
            ->queryOne();

        if (!is_array($row) || !isset($row['secret_hint']) || $row['secret_hint'] === null || $row['secret_hint'] === '') {
            return null;
        }

        return (string) $row['secret_hint'];
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): Channel
    {
        return ChannelFactory::fromRow($row, $this->fetchKnowledgeBaseIds((string) $row['id']));
    }

    /** @return list<string> */
    private function fetchKnowledgeBaseIds(string $channelId): array
    {
        $rows = $this->connection
            ->createCommand(
                'SELECT knowledge_base_id
                 FROM channel_knowledge_bases
                 WHERE channel_id = :channel_id
                 ORDER BY sort_order ASC, knowledge_base_id ASC',
            )
            ->bindValue(':channel_id', $channelId)
            ->queryAll();

        $ids = [];

        foreach ($rows as $row) {
            if (is_array($row) && isset($row['knowledge_base_id'])) {
                $ids[] = (string) $row['knowledge_base_id'];
            }
        }

        return $ids;
    }

    private function syncKnowledgeBases(Channel $channel): void
    {
        $this->connection
            ->createCommand('DELETE FROM channel_knowledge_bases WHERE channel_id = :channel_id')
            ->bindValue(':channel_id', $channel->getId())
            ->execute();

        $sortOrder = 0;
        $createdAt = $channel->getUpdatedAt()->format('Y-m-d H:i:sP');

        foreach ($channel->getKnowledgeBaseIds() as $knowledgeBaseId) {
            $this->connection
                ->createCommand(
                    'INSERT INTO channel_knowledge_bases (channel_id, knowledge_base_id, sort_order, created_at)
                     VALUES (:channel_id, :knowledge_base_id, :sort_order, :created_at)',
                )
                ->bindValues([
                    ':channel_id' => $channel->getId(),
                    ':knowledge_base_id' => $knowledgeBaseId,
                    ':sort_order' => $sortOrder,
                    ':created_at' => $createdAt,
                ])
                ->execute();

            ++$sortOrder;
        }
    }

    private function upsertSecret(string $channelId, string $secretPayloadJson): void
    {
        $hint = $this->buildSecretHint($secretPayloadJson);
        $encrypted = $this->secretEncryptor->encrypt($secretPayloadJson);
        $updatedAt = (new DateTimeImmutable())->format('Y-m-d H:i:sP');

        $this->connection
            ->createCommand(
                'INSERT INTO channel_secrets (channel_id, payload_encrypted, secret_hint, updated_at)
                 VALUES (:channel_id, :payload_encrypted, :secret_hint, :updated_at)
                 ON CONFLICT (channel_id) DO UPDATE SET
                    payload_encrypted = EXCLUDED.payload_encrypted,
                    secret_hint = EXCLUDED.secret_hint,
                    updated_at = EXCLUDED.updated_at',
            )
            ->bindValues([
                ':channel_id' => $channelId,
                ':payload_encrypted' => $encrypted,
                ':secret_hint' => $hint,
                ':updated_at' => $updatedAt,
            ])
            ->execute();
    }

    private function buildSecretHint(string $secretPayloadJson): ?string
    {
        try {
            $payload = json_decode($secretPayloadJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!is_array($payload)) {
            return null;
        }

        $token = $payload['bot_token'] ?? $payload['access_token'] ?? null;

        if (!is_string($token) || $token === '') {
            return null;
        }

        return mb_strlen($token) >= 4 ? mb_substr($token, -4) : $token;
    }
}
