<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram;

use App\Application\Channel\Telegram\HandleUpdate\HandleTelegramUpdateHandler;
use App\Application\Channel\Telegram\TelegramBotApiInterface;
use App\Application\Channel\Telegram\TelegramBotInfo;
use InvalidArgumentException;
use RuntimeException;

final class TelegramBotApiHttpClient implements TelegramBotApiInterface
{
    public function getMe(string $botToken): TelegramBotInfo
    {
        $decoded = $this->call($botToken, 'getMe');
        $result = $this->requireResultArray($decoded);

        $id = $result['id'] ?? null;
        $username = $result['username'] ?? null;

        if (!is_int($id) && !is_string($id)) {
            throw new RuntimeException('Telegram API не вернул id бота.');
        }

        if (!is_string($username) || $username === '') {
            throw new RuntimeException('Telegram API не вернул username бота.');
        }

        return new TelegramBotInfo(
            id: (string) $id,
            username: $username,
        );
    }

    public function setWebhook(
        string $botToken,
        string $url,
        string $secretToken,
        array $allowedUpdates,
    ): void {
        $this->call($botToken, 'setWebhook', [
            'url' => $url,
            'secret_token' => $secretToken,
            'allowed_updates' => array_values($allowedUpdates),
            'drop_pending_updates' => true,
        ]);
    }

    public function sendMessage(
        string $botToken,HandleTelegramUpdateHandler
        int|string $chatId,
        string $text,
        ?string $businessConnectionId = null,
        ?int $replyToMessageId = null,
    ): void {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($businessConnectionId !== null && $businessConnectionId !== '') {
            $payload['business_connection_id'] = $businessConnectionId;
        }

        if ($replyToMessageId !== null) {
            $payload['reply_to_message_id'] = $replyToMessageId;
        }

        $this->call($botToken, 'sendMessage', $payload);
    }

    /** @param array<string, mixed> $payload */
    private function call(string $botToken, string $method, array $payload = []): array
    {
        $url = 'https://api.telegram.org/bot' . $botToken . '/' . $method;
        $body = $payload === [] ? null : json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $responseBody = $this->request($url, $body);
        $decoded = json_decode($responseBody, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Некорректный ответ Telegram API.');
        }

        if (($decoded['ok'] ?? false) !== true) {
            $description = is_string($decoded['description'] ?? null)
                ? (string) $decoded['description']
                : 'неизвестная ошибка';

            throw new InvalidArgumentException('Telegram API: ' . $description);
        }

        return $decoded;
    }

    /** @return array<string, mixed> */
    private function requireResultArray(array $decoded): array
    {
        $result = $decoded['result'] ?? null;

        if (!is_array($result)) {
            throw new RuntimeException('Некорректный ответ Telegram API.');
        }

        $normalized = [];

        foreach ($result as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    private function request(string $url, ?string $jsonBody = null): string
    {
        if (function_exists('curl_init')) {
            $curlHandle = curl_init($url);

            if ($curlHandle === false) {
                throw new RuntimeException('Не удалось инициализировать HTTP-запрос к Telegram.');
            }

            $headers = [];
            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 5,
            ];

            if ($jsonBody !== null) {
                $headers[] = 'Content-Type: application/json';
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = $jsonBody;
            }

            if ($headers !== []) {
                $options[CURLOPT_HTTPHEADER] = $headers;
            }

            curl_setopt_array($curlHandle, $options);

            $responseBody = curl_exec($curlHandle);
            $statusCode = (int) curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curlHandle);
            curl_close($curlHandle);

            if ($responseBody === false) {
                throw new RuntimeException('Ошибка запроса к Telegram API: ' . $curlError);
            }

            if ($statusCode >= 500) {
                throw new RuntimeException('Telegram API временно недоступен.');
            }

            return $responseBody;
        }

        $http = [
            'method' => $jsonBody === null ? 'GET' : 'POST',
            'timeout' => 15,
            'ignore_errors' => true,
            'header' => $jsonBody === null ? '' : "Content-Type: application/json\r\n",
        ];

        if ($jsonBody !== null) {
            $http['content'] = $jsonBody;
        }

        $context = stream_context_create(['http' => $http]);
        $responseBody = @file_get_contents($url, false, $context);

        if ($responseBody === false) {
            throw new RuntimeException('Не удалось выполнить запрос к Telegram API.');
        }

        return $responseBody;
    }
}
