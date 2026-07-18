<?php

declare(strict_types=1);

namespace App\Web\Shared\Layout;

use App\Application\Auth\AuthSessionService;
use App\Domain\User\UserRepositoryInterface;
use Yiisoft\Yii\View\Renderer\CommonParametersInjectionInterface;

final class AuthViewInjection implements CommonParametersInjectionInterface
{
    public function __construct(
        private readonly AuthSessionService $authSessionService,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function getCommonParameters(): array
    {
        $userId = $this->authSessionService->getUserId();
        $user = $userId !== null ? $this->userRepository->findById($userId) : null;
        $isAuthenticated = $user !== null;

        return [
            'isAuthenticated' => $isAuthenticated,
            'userEmail' => $user?->getEmail(),
            'includeAuthModal' => !$isAuthenticated,
        ];
    }
}
