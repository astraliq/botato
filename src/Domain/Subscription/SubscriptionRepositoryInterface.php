<?php

declare(strict_types=1);

namespace App\Domain\Subscription;

interface SubscriptionRepositoryInterface
{
    public function findActiveByUserId(string $userId): ?Subscription;

    /** Создаёт active Free + m1, если у пользователя ещё нет active-подписки. */
    public function ensureFreeSubscription(string $userId): Subscription;
}
