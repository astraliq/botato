<?php

declare(strict_types=1);

namespace App\Application\Auth\GetCurrentUser;

use App\Application\Auth\AuthSessionInterface;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use RuntimeException;

final class GetCurrentUserHandler
{
    public function __construct(
        private readonly AuthSessionInterface $authSession,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function handle(): User
    {
        $userId = $this->authSession->getUserId();

        if ($userId === null) {
            throw new RuntimeException('Требуется авторизация.');
        }

        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new RuntimeException('Пользователь не найден.');
        }

        return $user;
    }

    public function handleOrNull(): ?User
    {
        $userId = $this->authSession->getUserId();

        if ($userId === null) {
            return null;
        }

        return $this->userRepository->findById($userId);
    }
}
