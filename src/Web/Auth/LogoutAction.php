<?php

declare(strict_types=1);

namespace App\Web\Auth;

use App\Application\Auth\AuthSessionService;
use App\Web\Shared\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\UrlGeneratorInterface;

final class LogoutAction
{
    public function __construct(
        private readonly AuthSessionService $authSessionService,
        private readonly JsonResponseFactory $jsonResponseFactory,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $this->authSessionService->logout();

        return $this->jsonResponseFactory->success([
            'message' => 'Вы вышли из аккаунта.',
            'redirect' => $this->urlGenerator->generate('home'),
        ]);
    }
}
