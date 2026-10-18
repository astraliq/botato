<?php

declare(strict_types=1);

namespace App\Application\Admin\Ai\SaveModel;

use App\Domain\Ai\AiModel;
use App\Domain\Ai\AiModelPurpose;
use App\Domain\Ai\AiProviderRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final readonly class SaveAiModelHandler
{
    public function __construct(
        private AiProviderRepositoryInterface $aiProviderRepository,
    ) {}

    /**
     * @param array<string, mixed> $formData
     */
    public function handle(string $providerId, ?string $modelId, array $formData): AiModel
    {
        $provider = $this->aiProviderRepository->findById($providerId);

        if ($provider === null || $provider->isDeleted()) {
            throw new InvalidArgumentException('Провайдер не найден.');
        }

        $name = trim((string) ($formData['name'] ?? ''));
        $purposeValue = trim((string) ($formData['purpose'] ?? ''));
        $sortOrder = (int) ($formData['sort_order'] ?? 0);
        $isActive = isset($formData['is_active']);
        $isDefault = isset($formData['is_default']);

        if ($name === '') {
            throw new InvalidArgumentException('Укажите название модели.');
        }

        $purpose = AiModelPurpose::tryFrom($purposeValue);

        if ($purpose === null) {
            throw new InvalidArgumentException('Некорректный purpose модели.');
        }

        $now = new DateTimeImmutable();

        if ($modelId === null || $modelId === '') {
            $code = trim((string) ($formData['code'] ?? ''));

            if ($code === '') {
                throw new InvalidArgumentException('Укажите код модели.');
            }

            $model = AiModel::create(
                id: Uuid::uuid4()->toString(),
                providerId: $providerId,
                code: $code,
                name: $name,
                purpose: $purpose,
                isActive: $isActive,
                isDefault: $isDefault,
                sortOrder: $sortOrder,
                settings: [],
                createdAt: $now,
            );

            if ($isDefault) {
                $this->aiProviderRepository->clearDefaultModelsExcept(
                    $providerId,
                    $purpose,
                    $model->getId(),
                );
            }

            $this->aiProviderRepository->saveModel($model);

            return $model;
        }

        $model = $this->aiProviderRepository->findModelById($modelId);

        if ($model === null || $model->getProviderId() !== $providerId) {
            throw new InvalidArgumentException('Модель не найдена.');
        }

        $model->update(
            name: $name,
            purpose: $purpose,
            isActive: $isActive,
            isDefault: $isDefault,
            sortOrder: $sortOrder,
            settings: $model->getSettings(),
            updatedAt: $now,
        );

        if ($isDefault) {
            $this->aiProviderRepository->clearDefaultModelsExcept(
                $providerId,
                $purpose,
                $model->getId(),
            );
        }

        $this->aiProviderRepository->saveModel($model);

        return $model;
    }
}
