<?php

declare(strict_types=1);

return [
    'database' => [
        'dsn' => getenv('DB_DSN') ?: 'pgsql:host=postgres;port=5432;dbname=botato',
        'username' => getenv('DB_USER') ?: 'app',
        'password' => getenv('DB_PASSWORD') ?: 'secret',
    ],
    'mailer' => [
        'dsn' => getenv('MAILER_DSN') ?: 'smtp://mailpit:1025',
        'from' => getenv('MAILER_FROM') ?: 'noreply@botato.local',
    ],
];
