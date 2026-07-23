<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Support\SupportTicket;
use App\Domain\Support\SupportTicketRepositoryInterface;
use App\Shared\ArrayHelper;
use Yiisoft\Db\Connection\ConnectionInterface;

final class SupportTicketRepository implements SupportTicketRepositoryInterface
{
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {}

    public function listByUserId(string $userId): array
    {
        $rows = $this->connection
            ->createCommand(
                'SELECT * FROM support_tickets
                 WHERE user_id = :user_id
                 ORDER BY created_at DESC',
            )
            ->bindValue(':user_id', $userId)
            ->queryAll();

        $tickets = [];

        foreach ($rows as $row) {
            $tickets[] = SupportTicketFactory::fromRow(ArrayHelper::toStringKeyArray($row));
        }

        return $tickets;
    }

    public function save(SupportTicket $ticket): void
    {
        $existing = $this->connection
            ->createCommand('SELECT 1 FROM support_tickets WHERE id = :id')
            ->bindValue(':id', $ticket->getId())
            ->queryScalar();

        if ($existing === false || $existing === null) {
            $this->connection
                ->createCommand(
                    'INSERT INTO support_tickets (
                        id, user_id, question, is_closed, created_at, updated_at
                     ) VALUES (
                        :id, :user_id, :question, :is_closed, :created_at, :updated_at
                     )',
                )
                ->bindValues([
                    ':id' => $ticket->getId(),
                    ':user_id' => $ticket->getUserId(),
                    ':question' => $ticket->getQuestion(),
                    ':is_closed' => $ticket->isClosed(),
                    ':created_at' => $ticket->getCreatedAt()->format('Y-m-d H:i:sP'),
                    ':updated_at' => $ticket->getUpdatedAt()->format('Y-m-d H:i:sP'),
                ])
                ->execute();

            return;
        }

        $this->connection
            ->createCommand(
                'UPDATE support_tickets SET
                    is_closed = :is_closed,
                    updated_at = :updated_at
                 WHERE id = :id',
            )
            ->bindValues([
                ':id' => $ticket->getId(),
                ':is_closed' => $ticket->isClosed(),
                ':updated_at' => $ticket->getUpdatedAt()->format('Y-m-d H:i:sP'),
            ])
            ->execute();
    }
}
