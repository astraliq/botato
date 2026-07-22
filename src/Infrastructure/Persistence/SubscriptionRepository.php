<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Subscription\BillingPeriodCode;
use App\Domain\Subscription\PlanCode;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use App\Domain\Subscription\SubscriptionStatus;
use App\Shared\ArrayHelper;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Yiisoft\Db\Connection\ConnectionInterface;

final class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {}

    public function findActiveByUserId(string $userId): ?Subscription
    {
        $row = $this->connection
            ->createCommand(
                'SELECT * FROM subscriptions
                 WHERE user_id = :user_id AND status = :status
                 LIMIT 1',
            )
            ->bindValues([
                ':user_id' => $userId,
                ':status' => SubscriptionStatus::Active->value,
            ])
            ->queryOne();

        return is_array($row) ? SubscriptionFactory::fromRow(ArrayHelper::toStringKeyArray($row)) : null;
    }

    public function ensureFreeSubscription(string $userId): Subscription
    {
        $existing = $this->findActiveByUserId($userId);

        if ($existing !== null) {
            return $existing;
        }

        $plan = $this->connection
            ->createCommand(
                'SELECT * FROM plans
                 WHERE code = :code AND is_active = TRUE AND deleted = FALSE',
            )
            ->bindValue(':code', PlanCode::Free->value)
            ->queryOne();

        $period = $this->connection
            ->createCommand('SELECT * FROM billing_periods WHERE code = :code AND is_active = TRUE')
            ->bindValue(':code', BillingPeriodCode::M1->value)
            ->queryOne();

        if (!is_array($plan) || !is_array($period)) {
            throw new RuntimeException('Не найдены тариф Free или период m1.');
        }

        $now = new DateTimeImmutable();
        $id = Uuid::uuid4()->toString();
        $nowFormatted = $now->format('Y-m-d H:i:sP');
        $endsAt = $now->modify('+100 years')->format('Y-m-d H:i:sP');

        $this->connection
            ->createCommand(
                'INSERT INTO subscriptions (
                    id, user_id, plan_id, billing_period_id, status,
                    price_monthly_minor, amount_charged_minor, currency,
                    max_dialogs_per_month, max_channels, max_knowledge_bases,
                    starts_at, ends_at, created_at, updated_at
                 ) VALUES (
                    :id, :user_id, :plan_id, :billing_period_id, :status,
                    :price_monthly_minor, :amount_charged_minor, :currency,
                    :max_dialogs_per_month, :max_channels, :max_knowledge_bases,
                    :starts_at, :ends_at, :created_at, :updated_at
                 )',
            )
            ->bindValues([
                ':id' => $id,
                ':user_id' => $userId,
                ':plan_id' => (string) $plan['id'],
                ':billing_period_id' => (string) $period['id'],
                ':status' => SubscriptionStatus::Active->value,
                ':price_monthly_minor' => (int) $plan['price_monthly_minor'],
                ':amount_charged_minor' => 0,
                ':currency' => 'RUB',
                ':max_dialogs_per_month' => (int) $plan['max_dialogs_per_month'],
                ':max_channels' => $plan['max_channels'] !== null ? (int) $plan['max_channels'] : null,
                ':max_knowledge_bases' => (int) $plan['max_knowledge_bases'],
                ':starts_at' => $nowFormatted,
                ':ends_at' => $endsAt,
                ':created_at' => $nowFormatted,
                ':updated_at' => $nowFormatted,
            ])
            ->execute();

        $subscription = $this->findActiveByUserId($userId);

        if ($subscription === null) {
            throw new RuntimeException('Не удалось создать Free-подписку.');
        }

        return $subscription;
    }
}
