<?php

declare(strict_types=1);

namespace App\Web\Cabinet\Onboarding;

use App\Application\Auth\GetCurrentUser\GetCurrentUserHandler;
use App\Application\Profile\UpdateProfile\UpdateProfileCommand;
use App\Application\Profile\UpdateProfile\UpdateProfileHandler;
use App\Web\Shared\JsonBodyParser;
use App\Web\Shared\JsonResponseFactory;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class SaveProfileAction
{
    public function __construct(
        private GetCurrentUserHandler $getCurrentUserHandler,
        private UpdateProfileHandler $updateProfileHandler,
        private JsonResponseFactory $jsonResponseFactory,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $user = $this->getCurrentUserHandler->handle();
            $command = UpdateProfileCommand::fromArray(JsonBodyParser::parse($request));
            $this->updateProfileHandler->handle($user->getId(), $command);

            return $this->jsonResponseFactory->success([
                'message' => 'Профиль сохранён.',
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->jsonResponseFactory->error($exception->getMessage());
        } catch (Throwable $exception) {
            $this->logger->error('Failed to save onboarding profile.', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return $this->jsonResponseFactory->error('Не удалось сохранить профиль.', 500);
        }
    }
}
