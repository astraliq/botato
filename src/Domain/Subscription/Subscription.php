<?php

declare(strict_types=1);

namespace App\Domain\Subscription;

use DateTimeImmutable;

final class Subscription
{
    public function __construct(
        private readonly string $id,
        private readonly string $userId,
        private readonly string $planId,
        private readonly string $billingPeriodId,
        private SubscriptionStatus $status,
        private readonly int $priceMonthlyMinor,
        private readonly int $amountChargedMinor,
        private readonly string $currency,
        private readonly int $maxDialogsPerMonth,
        private readonly int $maxMessagesPerDialog,
        private readonly ?int $maxChannels,
        private readonly int $maxKnowledgeBases,
        private readonly DateTimeImmutable $startsAt,
        private readonly DateTimeImmutable $endsAt,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getPlanId(): string
    {
        return $this->planId;
    }

    public function getBillingPeriodId(): string
    {
        return $this->billingPeriodId;
    }

    public function getStatus(): SubscriptionStatus
    {
        return $this->status;
    }

    public function getPriceMonthlyMinor(): int
    {
        return $this->priceMonthlyMinor;
    }

    public function getAmountChargedMinor(): int
    {
        return $this->amountChargedMinor;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getMaxDialogsPerMonth(): int
    {
        return $this->maxDialogsPerMonth;
    }

    public function getMaxMessagesPerDialog(): int
    {
        return $this->maxMessagesPerDialog;
    }

    public function getMaxChannels(): ?int
    {
        return $this->maxChannels;
    }

    public function getMaxKnowledgeBases(): int
    {
        return $this->maxKnowledgeBases;
    }

    public function getStartsAt(): DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function getEndsAt(): DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function hasUnlimitedChannels(): bool
    {
        return $this->maxChannels === null;
    }
}
