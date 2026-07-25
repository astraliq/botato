<?php

declare(strict_types=1);

namespace App\Web\Auth;

use App\Application\Auth\VerifyEmailCode\VerifyEmailCodeHandler;
use App\Shared\ArrayHelper;
use App\Web\Shared\JsonBodyParser;
use App\Web\Shared\JsonResponseFactory;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Yiisoft\Router\UrlGeneratorInterface;

final class VerifyCodeAction
{
    public function __construct(
        private readonly VerifyEmailCodeHandler $handler,
        private readonly JsonResponseFactory $jsonResponseFactory,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $payload = JsonBodyParser::parse($request);
            $email = ArrayHelper::toString($payload['email'] ?? null);
            $code = ArrayHelper::toString($payload['code'] ?? null);
            $user = $this->handler->handle($email, $code);
            $redirectRoute = $user->isOnboardingCompleted() ? 'cabinet' : 'cabinet/onboarding';

            return $this->jsonResponseFactory->success([
                'message' => 'Вход выполнен.',
                'redirect' => $this->urlGenerator->generate($redirectRoute),
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->jsonResponseFactory->error($exception->getMessage());
        } catch (Throwable $exception) {
            $this->logger->error('Failed to verify email code.', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return $this->jsonResponseFactory->error('Не удалось подтвердить код.', 500);
        }
    }
}
