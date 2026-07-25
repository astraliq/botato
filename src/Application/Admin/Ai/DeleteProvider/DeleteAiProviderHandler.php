<?php

declare(strict_types=1);

namespace App\Application\Admin\Ai\DeleteProvider;

use App\Domain\Ai\AiProviderRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final class DeleteAiProviderHandler
{
    public function __construct(
        private readonly AiProviderRepositoryInterface $aiProviderRepository,
    ) {}

    public function handle(string $providerId): void
    {
        $provider = $this->aiProviderRepository->findById($providerId);

        if ($provider === null || $provider->isDeleted()) {
            throw new InvalidArgumentException('Провайдер не найден.');
        }

        $provider->markDeleted(new DateTimeImmutable());
        $this->aiProviderRepository->save($provider);
    }
}
