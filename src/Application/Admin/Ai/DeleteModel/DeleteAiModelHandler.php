<?php

declare(strict_types=1);

namespace App\Application\Admin\Ai\DeleteModel;

use App\Domain\Ai\AiProviderRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class DeleteAiModelHandler
{
    public function __construct(
        private AiProviderRepositoryInterface $aiProviderRepository,
    ) {}

    public function handle(string $providerId, string $modelId): void
    {
        $model = $this->aiProviderRepository->findModelById($modelId);

        if ($model === null || $model->getProviderId() !== $providerId) {
            throw new InvalidArgumentException('Модель не найдена.');
        }

        $model->markDeleted(new DateTimeImmutable());
        $this->aiProviderRepository->saveModel($model);
    }
}
