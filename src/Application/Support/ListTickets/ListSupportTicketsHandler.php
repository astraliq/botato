<?php

declare(strict_types=1);

namespace App\Application\Support\ListTickets;

use App\Domain\Support\SupportTicket;
use App\Domain\Support\SupportTicketRepositoryInterface;

final class ListSupportTicketsHandler
{
    public function __construct(
        private readonly SupportTicketRepositoryInterface $supportTicketRepository,
    ) {}

    /** @return list<SupportTicket> */
    public function handle(string $userId): array
    {
        return $this->supportTicketRepository->listByUserId($userId);
    }
}
