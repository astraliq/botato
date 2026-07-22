<?php

declare(strict_types=1);

namespace App\Domain\Subscription;

enum PlanCode: string
{
    case Free = 'free';
    case Start = 'start';
    case Business = 'business';
    case Pro = 'pro';
}
