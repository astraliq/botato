<?php

declare(strict_types=1);

namespace App\Domain\Subscription;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Canceled = 'canceled';
}
