<?php

declare(strict_types=1);

namespace App\Infrastructure\Ai;

use App\Application\Ai\AiChatClientInterface;
use App\Application\Ai\AiChatMessage;
use App\Application\Ai\AiChatResult;
use App\Domain\Ai\AiModelPurpose;
use App\Domain\Ai\AiProviderRepositoryInterface;
use JsonException;
use RuntimeException;

final readonly class AiTunnelChatClient implements AiChatClientInterface
{
    public function __construct(
        private AiProviderRepositoryInterface $aiProviderRepository,
        private int $timeoutSeconds = 60,
    ) {}

    public function chat(array $messages, ?int $maxTokens = null): AiChatResult
    {
        $provider = $this->aiProviderRepository->findDefaultActive();

        if ($provider === null || !$provider->isActive()) {
            throw new RuntimeException('Не найден активный AI-провайдер по умолчанию.');
        }

        $model = $this->aiProviderRepository->findDefaultModelByProviderAndPurpose(
            $provider->getId(),
            AiModelPurpose::Chat,
        );

        if ($model === null) {
            throw new RuntimeException('Не найдена default-модель чата для AI-провайдера.');
        }

        $secretPayloadJson = $this->aiProviderRepository->getSecretPayload($provider->getId());

        if ($secretPayloadJson === null || $secretPayloadJson === '') {
            throw new RuntimeException('API-ключ AI-провайдера не настроен.');
        }

        try {
            $secretPayload = json_decode($secretPayloadJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Некорректный секрет AI-провайдера.', previous: $exception);
        }

        if (!is_array($secretPayload)) {
            throw new RuntimeException('Некорректный секрет AI-провайдера.');
        }

        $apiKey = $secretPayload['api_key'] ?? null;

        if (!is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('API-ключ AI-провайдера отсутствует.');
        }

        $settings = $provider->getSettings();
        $path = $settings['chat_completions_path'] ?? '/chat/completions';
        $defaultMaxTokens = $settings['default_max_tokens'] ?? 4096;

        if (!is_string($path) || $path === '') {
            $path = '/chat/completions';
        }

        if (!is_int($defaultMaxTokens)) {
            $defaultMaxTokens = 4096;
        }

        $baseUrl = rtrim($provider->getBaseUrl(), '/');
        $url = $baseUrl . '/' . ltrim($path, '/');

        $requestMessages = [];

        foreach ($messages as $message) {
            if (!$message instanceof AiChatMessage) {
                throw new RuntimeException('Некорректное сообщение для AI.');
            }

            $requestMessages[] = [
                'role' => $message->role,
                'content' => $message->content,
            ];
        }

        $body = [
            'model' => $model->getCode(),
            'messages' => $requestMessages,
            'max_tokens' => $maxTokens ?? $defaultMaxTokens,
        ];

        try {
            $jsonBody = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $exception) {
            throw new RuntimeException('Не удалось сериализовать запрос к AI.', previous: $exception);
        }

        $responseBody = $this->request($url, $apiKey, $jsonBody);
        $decoded = json_decode($responseBody, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Некорректный ответ AI API.');
        }

        $choices = $decoded['choices'] ?? null;

        if (!is_array($choices) || $choices === []) {
            throw new RuntimeException('AI API не вернул choices.');
        }

        $firstChoice = $choices[0] ?? null;

        if (!is_array($firstChoice)) {
            throw new RuntimeException('AI API вернул пустой choice.');
        }

        $messagePayload = $firstChoice['message'] ?? null;

        if (!is_array($messagePayload)) {
            throw new RuntimeException('AI API не вернул message.');
        }

        $content = $messagePayload['content'] ?? null;

        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('AI API вернул пустой ответ.');
        }

        $responseModel = $decoded['model'] ?? $model->getCode();

        return new AiChatResult(
            content: trim($content),
            model: is_string($responseModel) ? $responseModel : $model->getCode(),
        );
    }

    private function request(string $url, string $apiKey, string $jsonBody): string
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Расширение curl недоступно для AI-запросов.');
        }

        $curlHandle = curl_init($url);

        if ($curlHandle === false) {
            throw new RuntimeException('Не удалось инициализировать HTTP-запрос к AI.');
        }

        curl_setopt_array($curlHandle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
        ]);

        $responseBody = curl_exec($curlHandle);
        $statusCode = (int) curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curlHandle);
        curl_close($curlHandle);

        if ($responseBody === false || !is_string($responseBody)) {
            throw new RuntimeException('Ошибка запроса к AI API: ' . $curlError);
        }

        if ($statusCode >= 400) {
            throw new RuntimeException('AI API вернул HTTP ' . $statusCode . ': ' . mb_substr($responseBody, 0, 500));
        }

        return $responseBody;
    }
}
