<?php

declare(strict_types=1);

namespace App\Domain\Security;

interface SecretEncryptorInterface
{
    public function encrypt(string $plaintext): string;

    public function decrypt(string $ciphertext): string;
}
