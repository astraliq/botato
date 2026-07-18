<?php

declare(strict_types=1);

namespace App\Application\Auth;

interface VerificationCodeMailerInterface
{
    public function send(string $email, string $code): void;
}
