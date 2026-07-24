<?php

declare(strict_types=1);

namespace App\Domain\Usage;

use DateTimeImmutable;

/** Правила биллинга диалогов: блок из N сообщений с клиентом в календарном месяце = 1 диалог. */
final class DialogBillingRules
{
    public static function startsNewDialog(int $messageNumberInWindow, int $messagesPerDialog): bool
    {
        if ($messageNumberInWindow < 1 || $messagesPerDialog < 1) {
            return false;
        }

        return ($messageNumberInWindow - 1) % $messagesPerDialog === 0;
    }

    public static function isSameBillingMonth(DateTimeImmutable $left, DateTimeImmutable $right): bool
    {
        return $left->format('Y-m') === $right->format('Y-m');
    }
}
