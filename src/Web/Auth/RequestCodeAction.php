<?php

declare(strict_types=1);

namespace App\Web\Auth;

use App\Application\Auth\RequestVerificationCode\RequestVerificationCodeHandler;
use App\Web\Shared\JsonResponseFactory;
use App\Web\Shared\JsonBodyParser;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RequestCodeAction
{
    public function __construct(
        private readonly RequestVerificationCodeHandler $handler,
        private readonly JsonResponseFactory $jsonResponseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $payload = JsonBodyParser::parse($request);
            $email = is_string($payload['email'] ?? null) ? $payload['email'] : '';
            $this->handler->handle($email);

            return $this->jsonResponseFactory->success([
                'message' => 'Код отправлен на email.',
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->jsonResponseFactory->error($exception->getMessage());
        } catch (\Throwable) {
            return $this->jsonResponseFactory->error('Не удалось отправить код.', 500);
        }
    }
}
