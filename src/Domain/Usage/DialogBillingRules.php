<?php

declare(strict_types=1);

namespace App\Domain\Usage;

use DateTimeImmutable;

/** Правила биллинга: диалог — вся переписка с одним клиентом в течение 24 часов. */
final class DialogBillingRules
{
    public const DIALOG_TTL_SECONDS = 86400;

    public static function isWithinDialogWindow(DateTimeImmutable $windowStartedAt, DateTimeImmutable $now): bool
    {
        $expiresAt = $windowStartedAt->modify('+' . self::DIALOG_TTL_SECONDS . ' seconds');

        return $now < $expiresAt;
    }
}
