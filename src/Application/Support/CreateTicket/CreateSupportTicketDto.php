<?php

declare(strict_types=1);

namespace App\Application\Support\CreateTicket;

use App\Shared\ArrayHelper;

final readonly class CreateSupportTicketDto
{
    public function __construct(
        public string $question,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            question: ArrayHelper::toString($data['question'] ?? null),
        );
    }
}
