<?php

declare(strict_types=1);

namespace App\Application\Auth\Logout;

use App\Application\Auth\AuthSessionInterface;

final class LogoutHandler
{
    public function __construct(
        private readonly AuthSessionInterface $authSession,
    ) {}

    public function handle(): void
    {
        $this->authSession->logout();
    }
}
