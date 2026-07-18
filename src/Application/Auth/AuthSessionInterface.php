<?php

declare(strict_types=1);

namespace App\Application\Auth;

interface AuthSessionInterface
{
    public function login(string $userId): void;

    public function logout(): void;

    public function getUserId(): ?string;

    public function isAuthenticated(): bool;
}
