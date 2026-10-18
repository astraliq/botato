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

final readonly class AdminMiddleware implements MiddlewareInterface
{
    public function __construct(
        private GetCurrentUserHandler $getCurrentUserHandler,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $this->getCurrentUserHandler->handleOrNull();

        if ($user !== null && $user->isAdmin()) {
            return $handler->handle($request);
        }

        if ($this->isJsonRequest($request)) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Доступ запрещён.',
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

            return $response->withHeader('Content-Type', 'application/json; charset=UTF-8');
        }

        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $this->urlGenerator->generate('cabinet'));
    }

    private function isJsonRequest(ServerRequestInterface $request): bool
    {
        $acceptHeader = $request->getHeaderLine('Accept');

        return str_contains($acceptHeader, 'application/json')
            || str_contains($request->getUri()->getPath(), '/api/');
    }
}
