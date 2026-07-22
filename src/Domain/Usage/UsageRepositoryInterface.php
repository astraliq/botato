<?php

declare(strict_types=1);

namespace App\Domain\Usage;

use DateTimeImmutable;

interface UsageRepositoryInterface
{
    /**
     * Учитывает входящее сообщение peer в рамках календарного месяца
     * и списывает диалог, если начался новый блок из 10 сообщений.
     * При исчерпании месячного лимита сообщение не учитывается.
     */
    public function registerPeerMessage(
        string $userId,
        string $peerKey,
        int $maxDialogsPerMonth,
        DateTimeImmutable $now,
    ): PeerMessageQuotaResult;
}
