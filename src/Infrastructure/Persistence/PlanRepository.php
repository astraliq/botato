<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Plan\PlanRepositoryInterface;
use App\Shared\ArrayHelper;
use Yiisoft\Db\Connection\ConnectionInterface;

final class PlanRepository implements PlanRepositoryInterface
{
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {}

    public function listVisible(): array
    {
        $rows = $this->connection
            ->createCommand(
                'SELECT * FROM plans
                 WHERE deleted = FALSE
                 ORDER BY sort_order ASC',
            )
            ->queryAll();

        $plans = [];

        foreach ($rows as $row) {
            if (is_array($row)) {
                $plans[] = PlanFactory::fromRow(ArrayHelper::toStringKeyArray($row));
            }
        }

        return $plans;
    }
}
