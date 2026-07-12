<?php

declare(strict_types=1);

namespace App\Application\Auth;

use Yiisoft\Session\SessionInterface;

final class AuthSessionService
{
    private const SESSION_USER_ID_KEY = 'auth_user_id';

    public function __construct(
        private readonly SessionInterface $session,
    ) {}

    public function login(string $userId): void
    {
        $this->session->set(self::SESSION_USER_ID_KEY, $userId);
    }

    public function logout(): void
    {
        $this->session->remove(self::SESSION_USER_ID_KEY);
    }

    public function getUserId(): ?string
    {
        $userId = $this->session->get(self::SESSION_USER_ID_KEY);

        return is_string($userId) && $userId !== '' ? $userId : null;
    }

    public function isAuthenticated(): bool
    {
        return $this->getUserId() !== null;
    }
}
