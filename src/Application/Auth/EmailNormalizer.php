<?php

declare(strict_types=1);

namespace App\Application\Auth;

use InvalidArgumentException;

final class EmailNormalizer
{
    public static function normalize(string $email): string
    {
        $normalizedEmail = strtolower(trim($email));

        if ($normalizedEmail === '' || filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Укажите корректный email.');
        }

        return $normalizedEmail;
    }
}
