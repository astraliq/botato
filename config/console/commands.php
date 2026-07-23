<?php

declare(strict_types=1);

use App\Console;

return [
    'hello' => Console\HelloCommand::class,
    'migrate/up' => Console\MigrateUpCommand::class,
    'queue/consume' => Console\QueueConsumeCommand::class,
];
