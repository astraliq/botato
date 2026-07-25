<?php

declare(strict_types=1);

namespace App\Domain\Usage;

use DateTimeImmutable;

interface UsageRepositoryInterface
{
    /**
     * Учитывает входящее сообщение peer.
     * Диалог — переписка с клиентом в окне 24 часов; первое сообщение окна списывает 1 диалог из месячного лимита.
     * При исчерпании лимита диалогов или max_messages_per_dialog сообщение не учитывается.
     */
    public function registerPeerMessage(
        string $userId,
        string $peerKey,
        int $maxDialogsPerMonth,
        int $maxMessagesPerDialog,
        DateTimeImmutable $now,
    ): PeerMessageQuotaResult;
}
