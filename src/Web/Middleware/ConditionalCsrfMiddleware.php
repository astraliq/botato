<?php

declare(strict_types=1);

namespace App\Web\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Csrf\CsrfTokenMiddleware;

final readonly class ConditionalCsrfMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CsrfTokenMiddleware $csrfTokenMiddleware,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (str_starts_with($request->getUri()->getPath(), '/api/')
            || str_starts_with($request->getUri()->getPath(), '/webhooks/')
        ) {
            return $handler->handle($request);
        }

        return $this->csrfTokenMiddleware->process($request, $handler);
    }
}
