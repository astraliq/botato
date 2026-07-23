<?php

declare(strict_types=1);

namespace App\Domain\Queue;

enum QueueJobStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Done = 'done';
    case Failed = 'failed';
}
