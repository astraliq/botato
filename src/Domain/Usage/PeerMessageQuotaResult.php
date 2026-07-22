<?php

declare(strict_types=1);

namespace App\Domain\Usage;

final readonly class PeerMessageQuotaResult
{
    public function __construct(
        public bool $allowed,
        public bool $startedNewDialog,
        public int $messagesInWindow,
        public int $dialogsUsedThisMonth,
        public int $dialogsLimit,
    ) {}

    public function isLimitReached(): bool
    {
        return !$this->allowed;
    }
}
