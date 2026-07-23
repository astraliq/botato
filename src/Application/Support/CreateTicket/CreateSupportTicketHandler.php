<?php

declare(strict_types=1);

namespace App\Application\Support\CreateTicket;

use App\Domain\Support\SupportTicket;
use App\Domain\Support\SupportTicketRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class CreateSupportTicketHandler
{
    public function __construct(
        private readonly SupportTicketRepositoryInterface $supportTicketRepository,
    ) {}

    public function handle(string $userId, CreateSupportTicketDto $dto): SupportTicket
    {
        $question = trim($dto->question);

        if ($question === '') {
            throw new InvalidArgumentException('Введите текст вопроса.');
        }

        if (mb_strlen($question) > 65535) {
            throw new InvalidArgumentException('Вопрос не должен превышать 65535 символов.');
        }

        $ticket = SupportTicket::create(
            id: Uuid::uuid4()->toString(),
            userId: $userId,
            question: $question,
            createdAt: new DateTimeImmutable(),
        );

        $this->supportTicketRepository->save($ticket);

        return $ticket;
    }
}
