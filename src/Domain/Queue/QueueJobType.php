<?php

declare(strict_types=1);

namespace App\Domain\Queue;

final class QueueJobType
{
    public const TELEGRAM_PROCESS_MESSAGE = 'telegram.process_message';
}
