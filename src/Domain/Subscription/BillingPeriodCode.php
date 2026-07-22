<?php

declare(strict_types=1);

namespace App\Domain\Subscription;

enum BillingPeriodCode: string
{
    case M1 = 'm1';
    case M6 = 'm6';
    case M12 = 'm12';
}
