<?php

declare(strict_types=1);

namespace App\Web\Cabinet;

use App\Application\Auth\AuthSessionService;
use App\Application\Profile\UpdateProfile\UpdateProfileHandler;
use App\Domain\User\UserGender;
use App\Domain\User\UserRepositoryInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class UpdateAction
{
    public function __construct(
        private readonly ViewRenderer $viewRenderer,
        private readonly AuthSessionService $authSessionService,
        private readonly UserRepositoryInterface $userRepository,
        private readonly UpdateProfileHandler $updateProfileHandler,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $this->authSessionService->getUserId();

        if ($userId === null) {
            throw new \RuntimeException('Требуется авторизация.');
        }

        $parsedBody = $request->getParsedBody();
        $formData = is_array($parsedBody) ? $parsedBody : [];

        $errorMessage = null;
        $successMessage = null;
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new \RuntimeException('Пользователь не найден.');
        }

        $formValues = [
            'full_name' => (string) ($formData['full_name'] ?? $user->getFullName()),
            'company_name' => (string) ($formData['company_name'] ?? $user->getCompanyName()),
            'description' => (string) ($formData['description'] ?? $user->getDescription()),
            'phone' => (string) ($formData['phone'] ?? $user->getPhone()),
            'gender' => (string) ($formData['gender'] ?? $user->getGender()?->value ?? ''),
        ];

        try {
            $gender = UserGender::tryFrom($formValues['gender']);

            $user = $this->updateProfileHandler->handle(
                $userId,
                $formValues['full_name'],
                $formValues['company_name'],
                $formValues['description'],
                $formValues['phone'],
                $gender,
            );
            $successMessage = 'Профиль обновлён.';
            $formValues = [
                'full_name' => $user->getFullName(),
                'company_name' => $user->getCompanyName(),
                'description' => $user->getDescription(),
                'phone' => $user->getPhone(),
                'gender' => $user->getGender()?->value ?? '',
            ];
        } catch (InvalidArgumentException $exception) {
            $errorMessage = $exception->getMessage();
        }

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'user' => $user,
            'formValues' => $formValues,
            'successMessage' => $successMessage,
            'errorMessage' => $errorMessage,
            'isAuthenticated' => true,
            'includeAuthModal' => false,
            'includePhoneMask' => true,
            'includePricingScript' => false,
        ]);
    }
}
