<?php

declare(strict_types=1);

namespace App\Application\Admin\Ai\SaveProvider;

use App\Domain\Ai\AiApiStyle;
use App\Domain\Ai\AiProvider;
use App\Domain\Ai\AiProviderRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;
use Ramsey\Uuid\Uuid;
use RuntimeException;

final readonly class SaveAiProviderHandler
{
    public function __construct(
        private AiProviderRepositoryInterface $aiProviderRepository,
    ) {}

    /**
     * @param array<string, mixed> $formData
     */
    public function handle(?string $providerId, array $formData): AiProvider
    {
        $name = trim((string) ($formData['name'] ?? ''));
        $baseUrl = trim((string) ($formData['base_url'] ?? ''));
        $apiHost = trim((string) ($formData['api_host'] ?? ''));
        $apiStyleValue = trim((string) ($formData['api_style'] ?? ''));
        $sortOrder = (int) ($formData['sort_order'] ?? 0);
        $isActive = isset($formData['is_active']);
        $isDefault = isset($formData['is_default']);
        $chatCompletionsPath = trim((string) ($formData['chat_completions_path'] ?? '/chat/completions'));
        $defaultMaxTokens = (int) ($formData['default_max_tokens'] ?? 4096);
        $apiKey = trim((string) ($formData['api_key'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException('Укажите название провайдера.');
        }

        if ($baseUrl === '') {
            throw new InvalidArgumentException('Укажите base URL.');
        }

        if ($apiHost === '') {
            throw new InvalidArgumentException('Укажите API host.');
        }

        $apiStyle = AiApiStyle::tryFrom($apiStyleValue);

        if ($apiStyle === null) {
            throw new InvalidArgumentException('Некорректный API style.');
        }

        if ($chatCompletionsPath === '') {
            $chatCompletionsPath = '/chat/completions';
        }

        if ($defaultMaxTokens < 1) {
            throw new InvalidArgumentException('default_max_tokens должен быть больше 0.');
        }

        $settings = [
            'chat_completions_path' => $chatCompletionsPath,
            'default_max_tokens' => $defaultMaxTokens,
        ];

        $now = new DateTimeImmutable();
        $secretPayloadJson = null;

        if ($apiKey !== '') {
            try {
                $secretPayloadJson = json_encode(['api_key' => $apiKey], JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new RuntimeException('Не удалось сериализовать API-ключ.', previous: $exception);
            }
        }

        if ($providerId === null || $providerId === '') {
            $code = strtolower(trim((string) ($formData['code'] ?? '')));

            if ($code === '' || !preg_match('/^[a-z0-9_-]+$/', $code)) {
                throw new InvalidArgumentException('Код провайдера: латиница, цифры, _ и -.');
            }

            if ($this->aiProviderRepository->findByCode($code) !== null) {
                throw new InvalidArgumentException('Провайдер с таким кодом уже существует.');
            }

            $provider = AiProvider::create(
                id: Uuid::uuid4()->toString(),
                code: $code,
                name: $name,
                baseUrl: $baseUrl,
                apiHost: $apiHost,
                apiStyle: $apiStyle,
                isActive: $isActive,
                isDefault: $isDefault,
                sortOrder: $sortOrder,
                settings: $settings,
                createdAt: $now,
            );

            if ($isDefault) {
                $this->aiProviderRepository->clearDefaultProvidersExcept($provider->getId());
            }

            $this->aiProviderRepository->save($provider, $secretPayloadJson);

            return $provider;
        }

        $provider = $this->aiProviderRepository->findById($providerId);

        if ($provider === null || $provider->isDeleted()) {
            throw new InvalidArgumentException('Провайдер не найден.');
        }

        $provider->update(
            name: $name,
            baseUrl: $baseUrl,
            apiHost: $apiHost,
            apiStyle: $apiStyle,
            isActive: $isActive,
            isDefault: $isDefault,
            sortOrder: $sortOrder,
            settings: $settings,
            updatedAt: $now,
        );

        if ($isDefault) {
            $this->aiProviderRepository->clearDefaultProvidersExcept($provider->getId());
        }

        $this->aiProviderRepository->save($provider, $secretPayloadJson);

        return $provider;
    }
}
