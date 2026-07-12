<?php

declare(strict_types=1);

namespace App\Web\Cabinet;

use App\Application\Auth\AuthSessionService;
use App\Application\Profile\UpdateProfile\UpdateProfileHandler;
use App\Domain\User\UserGender;
use App\Domain\User\UserRepositoryInterface;
use App\Shared\ArrayHelper;
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
        $formData = is_array($parsedBody) ? ArrayHelper::toStringKeyArray($parsedBody) : [];

        $errorMessage = null;
        $successMessage = null;
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new \RuntimeException('Пользователь не найден.');
        }

        $formValues = [
            'full_name' => is_string($formData['full_name'] ?? null) ? $formData['full_name'] : $user->getFullName(),
            'company_name' => is_string($formData['company_name'] ?? null) ? $formData['company_name'] : $user->getCompanyName(),
            'description' => is_string($formData['description'] ?? null) ? $formData['description'] : $user->getDescription(),
            'phone' => is_string($formData['phone'] ?? null) ? $formData['phone'] : $user->getPhone(),
            'gender' => is_string($formData['gender'] ?? null) ? $formData['gender'] : ($user->getGender()?->value ?? ''),
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
