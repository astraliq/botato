<?php

declare(strict_types=1);

namespace App\Web\Cabinet;

use App\Application\Auth\GetCurrentUser\GetCurrentUserHandler;
use App\Application\Profile\ProfileFormValues;
use App\Application\Profile\UpdateProfile\UpdateProfileCommand;
use App\Application\Profile\UpdateProfile\UpdateProfileHandler;
use App\Shared\ArrayHelper;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class UpdateAction
{
    public function __construct(
        private readonly ViewRenderer $viewRenderer,
        private readonly GetCurrentUserHandler $getCurrentUserHandler,
        private readonly UpdateProfileHandler $updateProfileHandler,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->getCurrentUserHandler->handle();
        $parsedBody = $request->getParsedBody();
        $formData = is_array($parsedBody) ? ArrayHelper::toStringKeyArray($parsedBody) : [];
        $command = UpdateProfileCommand::fromArray($formData);

        $errorMessage = null;
        $successMessage = null;
        $formValues = $command->toFormValues();

        try {
            $user = $this->updateProfileHandler->handle($user->getId(), $command);
            $formValues = ProfileFormValues::fromUser($user);
            $successMessage = 'Профиль обновлён.';
        } catch (InvalidArgumentException $exception) {
            $errorMessage = $exception->getMessage();
        }

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'user' => $user,
            'formValues' => $formValues->toArray(),
            'successMessage' => $successMessage,
            'errorMessage' => $errorMessage,
        ]);
    }
}
