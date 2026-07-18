<?php

declare(strict_types=1);

namespace App\Web\Middleware;

use App\Application\Auth\AuthSessionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\UrlGeneratorInterface;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthSessionInterface $authSession,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->authSession->isAuthenticated()) {
            return $handler->handle($request);
        }

        if ($this->isJsonRequest($request)) {
            $response = $this->responseFactory->createResponse(401);
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Требуется авторизация.',
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

            return $response->withHeader('Content-Type', 'application/json; charset=UTF-8');
        }

        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $this->urlGenerator->generate('home'));
    }

    private function isJsonRequest(ServerRequestInterface $request): bool
    {
        $acceptHeader = $request->getHeaderLine('Accept');

        return str_contains($acceptHeader, 'application/json')
            || str_contains($request->getUri()->getPath(), '/api/');
    }
}
