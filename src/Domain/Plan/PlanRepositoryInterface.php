<?php

declare(strict_types=1);

namespace App\Domain\Plan;

interface PlanRepositoryInterface
{
    /** @return list<Plan> неудалённые планы по sort_order */
    public function listVisible(): array;
}
