<?php

declare(strict_types=1);

$mailerDsn = getenv('MAILER_DSN');
if (!is_string($mailerDsn) || $mailerDsn === '') {
    $mailerPassword = getenv('MAILER_PASSWORD');
    $mailerFrom = getenv('MAILER_FROM') ?: 'botato@list.ru';
    $mailerSmtpHost = getenv('MAILER_SMTP_HOST') ?: 'smtp.mail.ru';
    $mailerSmtpPort = getenv('MAILER_SMTP_PORT') ?: '465';

    if (is_string($mailerPassword) && $mailerPassword !== '') {
        $mailerDsn = sprintf(
            'smtps://%s:%s@%s:%s',
            rawurlencode($mailerFrom),
            rawurlencode($mailerPassword),
            $mailerSmtpHost,
            $mailerSmtpPort,
        );
    } else {
        $mailerDsn = 'smtp://mailpit:1025';
    }
}

return [
    'app_secret' => getenv('APP_SECRET') ?: 'dev-only-change-me-32bytes-key!!',
    'public_base_url' => getenv('APP_PUBLIC_URL') ?: '',
    'database' => [
        'dsn' => getenv('DB_DSN') ?: 'pgsql:host=postgres;port=5432;dbname=botato',
        'username' => getenv('DB_USER') ?: 'app',
        'password' => getenv('DB_PASSWORD') ?: 'secret',
    ],
    'redis' => [
        'host' => getenv('REDIS_HOST') ?: 'redis',
        'port' => (int) (getenv('REDIS_PORT') ?: 6379),
    ],
    'mailer' => [
        'dsn' => $mailerDsn,
        'from' => getenv('MAILER_FROM') ?: 'botato@list.ru',
    ],
];
