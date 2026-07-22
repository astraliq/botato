<?php

declare(strict_types=1);

namespace App\Domain\Usage;

use DateTimeImmutable;

/** Правила биллинга диалогов: до 10 сообщений с клиентом в календарном месяце = 1 диалог. */
final class DialogBillingRules
{
    public const MESSAGES_PER_DIALOG = 10;

    public static function startsNewDialog(int $messageNumberInWindow): bool
    {
        if ($messageNumberInWindow < 1) {
            return false;
        }

        return ($messageNumberInWindow - 1) % self::MESSAGES_PER_DIALOG === 0;
    }

    public static function isSameBillingMonth(DateTimeImmutable $left, DateTimeImmutable $right): bool
    {
        return $left->format('Y-m') === $right->format('Y-m');
    }
}
