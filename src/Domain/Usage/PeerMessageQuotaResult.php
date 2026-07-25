<?php

declare(strict_types=1);

namespace App\Domain\Usage;

use DateTimeImmutable;

final readonly class PeerMessageQuotaResult
{
    public const DENY_DIALOGS_LIMIT = 'dialogs_limit';
    public const DENY_MESSAGES_LIMIT = 'messages_limit';

    public function __construct(
        public bool $allowed,
        public bool $startedNewDialog,
        public int $messagesInWindow,
        public int $dialogsUsedThisMonth,
        public int $dialogsLimit,
        public DateTimeImmutable $windowStartedAt,
        public ?string $denyReason = null,
    ) {}

    public function isLimitReached(): bool
    {
        return !$this->allowed;
    }

    public function isDialogsLimitReached(): bool
    {
        return $this->denyReason === self::DENY_DIALOGS_LIMIT;
    }

    public function isMessagesLimitReached(): bool
    {
        return $this->denyReason === self::DENY_MESSAGES_LIMIT;
    }
}
