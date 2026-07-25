<?php

declare(strict_types=1);

namespace App\Web\Middleware;

use App\Application\Auth\GetCurrentUser\GetCurrentUserHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\UrlGeneratorInterface;

/** Редирект в онбординг, пока пользователь не завершил первичную настройку. */
final class OnboardingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly GetCurrentUserHandler $getCurrentUserHandler,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $this->getCurrentUserHandler->handleOrNull();

        if ($user === null || $user->isOnboardingCompleted()) {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();

        if (str_starts_with($path, '/cabinet/onboarding')) {
            return $handler->handle($request);
        }

        if (str_starts_with($path, '/api/')) {
            return $handler->handle($request);
        }

        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $this->urlGenerator->generate('cabinet/onboarding'));
    }
}
