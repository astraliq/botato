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
            'surname' => ArrayHelper::toString($formData['surname'] ?? null, $user->getSurname()),
            'name' => ArrayHelper::toString($formData['name'] ?? null, $user->getName()),
            'patronymic' => ArrayHelper::toString($formData['patronymic'] ?? null, $user->getPatronymic()),
            'company_name' => ArrayHelper::toString($formData['company_name'] ?? null, $user->getCompanyName()),
            'description' => ArrayHelper::toString($formData['description'] ?? null, $user->getDescription()),
            'phone' => ArrayHelper::toString($formData['phone'] ?? null, $user->getPhone()),
            'gender' => ArrayHelper::toString($formData['gender'] ?? null, $user->getGender()?->value ?? ''),
        ];

        try {
            $gender = UserGender::tryFrom($formValues['gender']);

            $user = $this->updateProfileHandler->handle(
                $userId,
                $formValues['surname'],
                $formValues['name'],
                $formValues['patronymic'],
                $formValues['company_name'],
                $formValues['description'],
                $formValues['phone'],
                $gender,
            );
            $successMessage = 'Профиль обновлён.';
            $formValues = [
                'surname' => $user->getSurname(),
                'name' => $user->getName(),
                'patronymic' => $user->getPatronymic(),
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
        ]);
    }
}
