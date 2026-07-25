<?php

declare(strict_types=1);

namespace App\Web\Shared\Layout;

use App\Application\Auth\GetCurrentUser\GetCurrentUserHandler;
use Yiisoft\Yii\View\Renderer\CommonParametersInjectionInterface;

final class AuthViewInjection implements CommonParametersInjectionInterface
{
    public function __construct(
        private readonly GetCurrentUserHandler $getCurrentUserHandler,
    ) {}

    public function getCommonParameters(): array
    {
        $user = $this->getCurrentUserHandler->handleOrNull();
        $isAuthenticated = $user !== null;

        return [
            'isAuthenticated' => $isAuthenticated,
            'userEmail' => $user?->getEmail(),
            'isAdmin' => $user?->isAdmin() ?? false,
            'includeAuthModal' => !$isAuthenticated,
        ];
    }
}
