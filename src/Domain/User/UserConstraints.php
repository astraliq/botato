<?php

declare(strict_types=1);

namespace App\Domain\User;

/**
 * Ограничения полей пользователя, согласованные со схемой БД.
 */
final class UserConstraints
{
    /**
     * users.description — PostgreSQL TEXT.
     * Практический лимит длины TEXT-поля (классический размер TEXT),
     * зафиксирован CHECK-ограничением в БД.
     */
    public const DESCRIPTION_MAX_LENGTH = 65_535;
}
