<?php

declare(strict_types=1);

namespace App\Web\Auth;

use App\Application\Auth\VerifyEmailCode\VerifyEmailCodeHandler;
use App\Web\Shared\JsonResponseFactory;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\UrlGeneratorInterface;

final class VerifyCodeAction
{
    public function __construct(
        private readonly VerifyEmailCodeHandler $handler,
        private readonly JsonResponseFactory $jsonResponseFactory,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $payload = $this->parseJsonBody($request);
            $email = (string) ($payload['email'] ?? '');
            $code = (string) ($payload['code'] ?? '');
            $this->handler->handle($email, $code);

            return $this->jsonResponseFactory->success([
                'message' => 'Вход выполнен.',
                'redirect' => $this->urlGenerator->generate('cabinet'),
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->jsonResponseFactory->error($exception->getMessage());
        } catch (\Throwable) {
            return $this->jsonResponseFactory->error('Не удалось подтвердить код.', 500);
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
