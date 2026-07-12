<?php

declare(strict_types=1);

namespace App\Web\Auth;

use App\Application\Auth\RequestVerificationCode\RequestVerificationCodeHandler;
use App\Web\Shared\JsonResponseFactory;
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
            $payload = $this->parseJsonBody($request);
            $email = (string) ($payload['email'] ?? '');
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

    /** @return array<string, mixed> */
    private function parseJsonBody(ServerRequestInterface $request): array
    {
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && $parsedBody !== []) {
            return $parsedBody;
        }

        $body = $request->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        $rawBody = (string) $body->getContents();
        $payload = json_decode($rawBody, true);

        return is_array($payload) ? $payload : [];
    }
}
