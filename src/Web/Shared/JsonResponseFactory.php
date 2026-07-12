<?php

declare(strict_types=1);

namespace App\Web\Shared;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class JsonResponseFactory
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
    ) {}

    /** @param array<string, mixed> $data */
    public function success(array $data, int $status = 200): ResponseInterface
    {
        return $this->json(['success' => true, ...$data], $status);
    }

    /** @param array<string, mixed> $data */
    public function error(string $message, int $status = 400, array $data = []): ResponseInterface
    {
        return $this->json(['success' => false, 'message' => $message, ...$data], $status);
    }

    /** @param array<string, mixed> $data */
    private function json(array $data, int $status): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        return $response->withHeader('Content-Type', 'application/json; charset=UTF-8');
    }
}
