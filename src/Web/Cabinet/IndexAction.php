<?php

declare(strict_types=1);

namespace App\Web\Cabinet;

use App\Application\Auth\AuthSessionService;
use App\Domain\User\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class IndexAction
{
    public function __construct(
        private readonly ViewRenderer $viewRenderer,
        private readonly AuthSessionService $authSessionService,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $userId = $this->authSessionService->getUserId();
        $user = $userId !== null ? $this->userRepository->findById($userId) : null;

        if ($user === null) {
            throw new \RuntimeException('Пользователь не найден.');
        }

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'user' => $user,
            'formValues' => [
                'full_name' => $user->getFullName(),
                'company_name' => $user->getCompanyName(),
                'description' => $user->getDescription(),
                'phone' => $user->getPhone(),
                'gender' => $user->getGender()?->value ?? '',
            ],
            'successMessage' => null,
            'errorMessage' => null,
            'isAuthenticated' => true,
            'includeAuthModal' => false,
            'includePhoneMask' => true,
            'includePricingScript' => false,
        ]);
    }
}
