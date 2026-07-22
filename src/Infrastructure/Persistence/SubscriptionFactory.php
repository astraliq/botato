<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\SubscriptionStatus;
use DateTimeImmutable;

final class SubscriptionFactory
{
    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): Subscription
    {
        return new Subscription(
            id: (string) $row['id'],
            userId: (string) $row['user_id'],
            planId: (string) $row['plan_id'],
            billingPeriodId: (string) $row['billing_period_id'],
            status: SubscriptionStatus::from((string) $row['status']),
            priceMonthlyMinor: (int) $row['price_monthly_minor'],
            amountChargedMinor: (int) $row['amount_charged_minor'],
            currency: (string) $row['currency'],
            maxDialogsPerMonth: (int) $row['max_dialogs_per_month'],
            maxChannels: isset($row['max_channels']) && $row['max_channels'] !== null
                ? (int) $row['max_channels']
                : null,
            maxKnowledgeBases: (int) $row['max_knowledge_bases'],
            startsAt: new DateTimeImmutable((string) $row['starts_at']),
            endsAt: new DateTimeImmutable((string) $row['ends_at']),
            createdAt: new DateTimeImmutable((string) $row['created_at']),
            updatedAt: new DateTimeImmutable((string) $row['updated_at']),
        );
    }
}
