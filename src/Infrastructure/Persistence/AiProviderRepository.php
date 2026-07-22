<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Ai\AiModel;
use App\Domain\Ai\AiModelPurpose;
use App\Domain\Ai\AiProvider;
use App\Domain\Ai\AiProviderRepositoryInterface;
use App\Domain\Security\SecretEncryptorInterface;
use App\Shared\ArrayHelper;
use DateTimeImmutable;
use Yiisoft\Db\Connection\ConnectionInterface;

final class AiProviderRepository implements AiProviderRepositoryInterface
{
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly SecretEncryptorInterface $secretEncryptor,
    ) {}

    public function findById(string $id): ?AiProvider
    {
        $row = $this->connection
            ->createCommand('SELECT * FROM ai_providers WHERE id = :id')
            ->bindValue(':id', $id)
            ->queryOne();

        return is_array($row) ? AiProviderFactory::fromRow(ArrayHelper::toStringKeyArray($row)) : null;
    }

    public function findByCode(string $code): ?AiProvider
    {
        $row = $this->connection
            ->createCommand(
                'SELECT * FROM ai_providers
                 WHERE code = :code AND deleted = FALSE
                 LIMIT 1',
            )
            ->bindValue(':code', $code)
            ->queryOne();

        return is_array($row) ? AiProviderFactory::fromRow(ArrayHelper::toStringKeyArray($row)) : null;
    }

    public function findDefaultActive(): ?AiProvider
    {
        $row = $this->connection
            ->createCommand(
                'SELECT * FROM ai_providers
                 WHERE is_default = TRUE AND is_active = TRUE AND deleted = FALSE
                 LIMIT 1',
            )
            ->queryOne();

        return is_array($row) ? AiProviderFactory::fromRow(ArrayHelper::toStringKeyArray($row)) : null;
    }

    public function listVisible(): array
    {
        $rows = $this->connection
            ->createCommand(
                'SELECT * FROM ai_providers
                 WHERE deleted = FALSE
                 ORDER BY sort_order ASC, name ASC',
            )
            ->queryAll();

        $providers = [];

        foreach ($rows as $row) {
            if (is_array($row)) {
                $providers[] = AiProviderFactory::fromRow(ArrayHelper::toStringKeyArray($row));
            }
        }

        return $providers;
    }

    public function listActiveModelsByProviderAndPurpose(string $providerId, AiModelPurpose $purpose): array
    {
        $rows = $this->connection
            ->createCommand(
                'SELECT * FROM ai_models
                 WHERE provider_id = :provider_id
                   AND purpose = :purpose
                   AND is_active = TRUE
                   AND deleted = FALSE
                 ORDER BY sort_order ASC, name ASC',
            )
            ->bindValues([
                ':provider_id' => $providerId,
                ':purpose' => $purpose->value,
            ])
            ->queryAll();

        return $this->mapModelRows($rows);
    }

    public function findDefaultModelByProviderAndPurpose(string $providerId, AiModelPurpose $purpose): ?AiModel
    {
        $row = $this->connection
            ->createCommand(
                'SELECT * FROM ai_models
                 WHERE provider_id = :provider_id
                   AND purpose = :purpose
                   AND is_default = TRUE
                   AND is_active = TRUE
                   AND deleted = FALSE
                 LIMIT 1',
            )
            ->bindValues([
                ':provider_id' => $providerId,
                ':purpose' => $purpose->value,
            ])
            ->queryOne();

        return is_array($row) ? AiModelFactory::fromRow(ArrayHelper::toStringKeyArray($row)) : null;
    }

    public function save(AiProvider $provider, ?string $secretPayloadJson = null): void
    {
        $existing = $this->findById($provider->getId());
        $settingsJson = json_encode($provider->getSettings(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        if ($existing === null) {
            $this->connection
                ->createCommand(
                    'INSERT INTO ai_providers (
                        id, code, name, base_url, api_host, api_style,
                        is_active, is_default, sort_order, settings,
                        deleted, created_at, updated_at
                     ) VALUES (
                        :id, :code, :name, :base_url, :api_host, :api_style,
                        :is_active, :is_default, :sort_order, CAST(:settings AS JSONB),
                        :deleted, :created_at, :updated_at
                     )',
                )
                ->bindValues([
                    ':id' => $provider->getId(),
                    ':code' => $provider->getCode(),
                    ':name' => $provider->getName(),
                    ':base_url' => $provider->getBaseUrl(),
                    ':api_host' => $provider->getApiHost(),
                    ':api_style' => $provider->getApiStyle()->value,
                    ':is_active' => $provider->isActive(),
                    ':is_default' => $provider->isDefault(),
                    ':sort_order' => $provider->getSortOrder(),
                    ':settings' => $settingsJson,
                    ':deleted' => $provider->isDeleted(),
                    ':created_at' => $provider->getCreatedAt()->format('Y-m-d H:i:sP'),
                    ':updated_at' => $provider->getUpdatedAt()->format('Y-m-d H:i:sP'),
                ])
                ->execute();
        } else {
            $this->connection
                ->createCommand(
                    'UPDATE ai_providers SET
                        name = :name,
                        base_url = :base_url,
                        api_host = :api_host,
                        api_style = :api_style,
                        is_active = :is_active,
                        is_default = :is_default,
                        sort_order = :sort_order,
                        settings = CAST(:settings AS JSONB),
                        deleted = :deleted,
                        updated_at = :updated_at
                     WHERE id = :id',
                )
                ->bindValues([
                    ':id' => $provider->getId(),
                    ':name' => $provider->getName(),
                    ':base_url' => $provider->getBaseUrl(),
                    ':api_host' => $provider->getApiHost(),
                    ':api_style' => $provider->getApiStyle()->value,
                    ':is_active' => $provider->isActive(),
                    ':is_default' => $provider->isDefault(),
                    ':sort_order' => $provider->getSortOrder(),
                    ':settings' => $settingsJson,
                    ':deleted' => $provider->isDeleted(),
                    ':updated_at' => $provider->getUpdatedAt()->format('Y-m-d H:i:sP'),
                ])
                ->execute();
        }

        if ($secretPayloadJson !== null) {
            $this->upsertSecret($provider->getId(), $secretPayloadJson);
        }
    }

    public function getSecretPayload(string $providerId): ?string
    {
        $row = $this->connection
            ->createCommand(
                'SELECT payload_encrypted FROM ai_provider_secrets WHERE provider_id = :provider_id',
            )
            ->bindValue(':provider_id', $providerId)
            ->queryOne();

        if (!is_array($row) || !isset($row['payload_encrypted'])) {
            return null;
        }

        return $this->secretEncryptor->decrypt((string) $row['payload_encrypted']);
    }

    public function findSecretHint(string $providerId): ?string
    {
        $row = $this->connection
            ->createCommand(
                'SELECT secret_hint FROM ai_provider_secrets WHERE provider_id = :provider_id',
            )
            ->bindValue(':provider_id', $providerId)
            ->queryOne();

        if (!is_array($row) || !isset($row['secret_hint']) || $row['secret_hint'] === null || $row['secret_hint'] === '') {
            return null;
        }

        return (string) $row['secret_hint'];
    }

    /**
     * @param list<mixed> $rows
     * @return list<AiModel>
     */
    private function mapModelRows(array $rows): array
    {
        $models = [];

        foreach ($rows as $row) {
            if (is_array($row)) {
                $models[] = AiModelFactory::fromRow(ArrayHelper::toStringKeyArray($row));
            }
        }

        return $models;
    }

    private function upsertSecret(string $providerId, string $secretPayloadJson): void
    {
        $hint = $this->buildSecretHint($secretPayloadJson);
        $encrypted = $this->secretEncryptor->encrypt($secretPayloadJson);
        $updatedAt = (new DateTimeImmutable())->format('Y-m-d H:i:sP');

        $this->connection
            ->createCommand(
                'INSERT INTO ai_provider_secrets (provider_id, payload_encrypted, secret_hint, updated_at)
                 VALUES (:provider_id, :payload_encrypted, :secret_hint, :updated_at)
                 ON CONFLICT (provider_id) DO UPDATE SET
                    payload_encrypted = EXCLUDED.payload_encrypted,
                    secret_hint = EXCLUDED.secret_hint,
                    updated_at = EXCLUDED.updated_at',
            )
            ->bindValues([
                ':provider_id' => $providerId,
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

        $apiKey = $payload['api_key'] ?? null;

        if (!is_string($apiKey) || $apiKey === '') {
            return null;
        }

        return mb_strlen($apiKey) >= 4 ? mb_substr($apiKey, -4) : $apiKey;
    }
}
