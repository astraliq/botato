<?php

declare(strict_types=1);

namespace App\Domain\Support;

interface SupportTicketRepositoryInterface
{
    /** @return list<SupportTicket> */
    public function listByUserId(string $userId): array;

    public function save(SupportTicket $ticket): void;
}
