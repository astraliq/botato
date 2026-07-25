<?php

declare(strict_types=1);

namespace App\Domain\Ai;

interface AiProviderRepositoryInterface
{
    public function findById(string $id): ?AiProvider;

    public function findByCode(string $code): ?AiProvider;

    public function findDefaultActive(): ?AiProvider;

    /** @return list<AiProvider> */
    public function listVisible(): array;

    /** @return list<AiProvider> */
    public function listAll(): array;

    /** @return list<AiModel> */
    public function listActiveModelsByProviderAndPurpose(string $providerId, AiModelPurpose $purpose): array;

    /** @return list<AiModel> */
    public function listModelsByProvider(string $providerId): array;

    public function findDefaultModelByProviderAndPurpose(string $providerId, AiModelPurpose $purpose): ?AiModel;

    public function findModelById(string $id): ?AiModel;

    public function save(AiProvider $provider, ?string $secretPayloadJson = null): void;

    public function saveModel(AiModel $model): void;

    public function clearDefaultProvidersExcept(string $providerId): void;

    public function clearDefaultModelsExcept(string $providerId, AiModelPurpose $purpose, string $modelId): void;

    public function getSecretPayload(string $providerId): ?string;

    public function findSecretHint(string $providerId): ?string;
}
